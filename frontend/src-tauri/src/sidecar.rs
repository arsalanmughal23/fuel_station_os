// Tauri Sidecar Manager for Laravel Backend (FrankenPHP Worker)
// Manages the Laravel backend as a FrankenPHP worker sidecar process
// Communicates via stdin/stdout JSON-RPC with the PHP worker

use std::io::{BufRead, BufReader, Write};
use std::path::PathBuf;
use std::process::{Child, ChildStdin, ChildStdout, Stdio};
use std::sync::{Arc, Mutex};
use std::time::Duration;
use serde::{Deserialize, Serialize};

use tokio::sync::mpsc;
use tracing::{error, info, warn};

/// Configuration for the FrankenPHP Worker sidecar
#[derive(Debug, Clone)]
pub struct SidecarConfig {
    pub php_path: String,
    pub laravel_root: String,
    pub worker_script: String,
    // Fields for future HTTP mode (currently unused in worker mode)
    _host: String,
    _port: u16,
    _health_check_endpoint: String,
    _max_restart_attempts: u32,
    _restart_delay: Duration,
}

impl Default for SidecarConfig {
    fn default() -> Self {
        // Try to get resource dir from Tauri's resource path, fallback to current_exe for dev
        let resource_dir = Self::get_resource_dir()
            .unwrap_or_else(|| std::path::PathBuf::from("."));

        Self {
            php_path: "php".to_string(),
            laravel_root: resource_dir.join("backend").to_string_lossy().to_string(),
            worker_script: "frankenphp-worker.php".to_string(),
            _host: "127.0.0.1".to_string(),
            _port: 8000,
            _health_check_endpoint: "/health".to_string(),
            _max_restart_attempts: 3,
            _restart_delay: Duration::from_secs(5),
        }
    }
}

impl SidecarConfig {
    /// Get the Tauri resource directory
    /// On Linux .deb installs, resources are at /usr/share/<appname>/resources/
    /// In development, resources are at src-tauri/resources/
    fn get_resource_dir() -> Option<PathBuf> {
        // Try Tauri's resource dir environment variable (set in bundled apps)
        if let Ok(resource_dir) = std::env::var("TAURI_RESOURCE_DIR") {
            return Some(PathBuf::from(resource_dir));
        }
        // Fallback: try to compute from executable path
        // Development: executable at src-tauri/target/debug/release/fuel_station_os
        // resources at src-tauri/resources
        // Production .deb: executable at /usr/bin/fuel_station_os
        // resources at /usr/share/fuel_station_os/resources
        std::env::current_exe().ok().and_then(|p| {
            // Try development path first (3 levels up: target/debug -> target -> src-tauri)
            let dev_resources = p.parent().and_then(|p| p.parent()).and_then(|p| p.parent()).map(|p| p.join("resources"));
            if dev_resources.as_ref().map_or(false, |p| p.exists()) {
                return dev_resources;
            }
            // Try production .deb path (/usr/bin -> /usr/share/<appname>/resources)
            let prod_resources = p.parent().and_then(|p| p.parent()).map(|p| p.join("share").join("fuel_station_os").join("resources"));
            if prod_resources.as_ref().map_or(false, |p| p.exists()) {
                return prod_resources;
            }
            // Try AppImage path (resources next to executable)
            let appimage_resources = p.parent().map(|p| p.join("resources"));
            if appimage_resources.as_ref().map_or(false, |p| p.exists()) {
                return appimage_resources;
            }
            dev_resources // fallback to dev path even if not exists
        })
    }
}

/// State of the sidecar process
#[derive(Debug, Clone, PartialEq, Serialize, Deserialize)]
pub enum SidecarState {
    Stopped,
    Starting,
    Running,
    Stopping,
    Error(String),
}

/// JSON-RPC request to PHP worker
#[derive(Debug, Serialize)]
#[serde(tag = "command")]
pub enum WorkerRequest {
    #[serde(rename = "health")]
    Health,
    #[serde(rename = "shutdown")]
    Shutdown,
    // Reserved for future HTTP proxy mode
    #[allow(dead_code)]
    Http {
        method: String,
        uri: String,
        headers: std::collections::HashMap<String, String>,
        query: std::collections::HashMap<String, String>,
        body: Option<serde_json::Value>,
    },
}

/// JSON-RPC response from PHP worker
#[derive(Debug, Deserialize)]
#[serde(untagged)]
pub enum WorkerResponse {
    Health {
        status: String,
        // Fields from PHP worker (currently unused)
        _pid: u32,
        _memory_mb: f64,
    },
    Shutdown {
        _status: String,
    },
    // Reserved for future HTTP proxy mode
    #[allow(dead_code)]
    Http {
        _status: u16,
        _headers: std::collections::HashMap<String, Vec<String>>,
        _content: String,
    },
    Error {
        error: serde_json::Value,
    },
}

/// Manages the Laravel backend FrankenPHP Worker sidecar process
/// Communicates via stdin/stdout JSON-RPC
pub struct LaravelSidecar {
    config: SidecarConfig,
    state: Arc<Mutex<SidecarState>>,
    worker_stdin: Arc<Mutex<Option<ChildStdin>>>,
    worker_stdout: Arc<Mutex<Option<BufReader<ChildStdout>>>>,
    worker_process: Arc<Mutex<Option<Child>>>,
    shutdown_tx: Arc<Mutex<Option<mpsc::Sender<()>>>>,
}

impl LaravelSidecar {
    /// Create a new LaravelSidecar instance
    pub fn new(config: SidecarConfig) -> Self {
        Self {
            config,
            state: Arc::new(Mutex::new(SidecarState::Stopped)),
            worker_stdin: Arc::new(Mutex::new(None)),
            worker_stdout: Arc::new(Mutex::new(None)),
            worker_process: Arc::new(Mutex::new(None)),
            shutdown_tx: Arc::new(Mutex::new(None)),
        }
    }

    /// Get the current state of the sidecar
    pub fn state(&self) -> SidecarState {
        self.state.lock().unwrap().clone()
    }

    /// Start the Laravel backend (FrankenPHP Worker)
    pub fn start(&self) -> Result<(), String> {
        let mut state = self.state.lock().unwrap();
        if *state != SidecarState::Stopped {
            return Err("Sidecar is not in stopped state".to_string());
        }
        *state = SidecarState::Starting;
        drop(state);

        // Create shutdown channel
        let (shutdown_tx, shutdown_rx) = mpsc::channel(1);
        *self.shutdown_tx.lock().unwrap() = Some(shutdown_tx);

        // Start PHP Worker
        self.start_worker()?;

        // Wait for health check via JSON-RPC
        self.wait_for_healthy()?;

        *self.state.lock().unwrap() = SidecarState::Running;
        info!("Laravel sidecar (worker) started successfully");

        // Monitor process
        self.monitor_process(shutdown_rx);

        Ok(())
    }

    /// Start PHP Worker process (frankenphp-worker.php)
    fn start_worker(&self) -> Result<(), String> {
        let mut cmd = std::process::Command::new(&self.config.php_path);
        
        cmd.arg(&self.config.worker_script)
            .current_dir(&self.config.laravel_root)
            .stdin(Stdio::piped())
            .stdout(Stdio::piped())
            .stderr(Stdio::piped());

        // Set environment variables for Laravel
        cmd.env("LARAVEL_ROOT", &self.config.laravel_root);

        let mut child = cmd.spawn()
            .map_err(|e| format!("Failed to start PHP worker: {}", e))?;

        // Get stdin/stdout handles
        let stdin = child.stdin.take().ok_or("Failed to get worker stdin")?;
        let stdout = child.stdout.take().ok_or("Failed to get worker stdout")?;
        let stderr = child.stderr.take().ok_or("Failed to get worker stderr")?;

        // Spawn stderr logging thread
        std::thread::spawn(move || {
            let reader = BufReader::new(stderr);
            for line in reader.lines() {
                if let Ok(line) = line {
                    tracing::debug!("[PHP Worker stderr] {}", line);
                }
            }
        });

        let stdout_reader = BufReader::new(stdout);

        *self.worker_stdin.lock().unwrap() = Some(stdin);
        *self.worker_stdout.lock().unwrap() = Some(stdout_reader);
        *self.worker_process.lock().unwrap() = Some(child);

        info!("PHP Worker started");

        Ok(())
    }

    /// Health check via JSON-RPC
    fn wait_for_healthy(&self) -> Result<(), String> {
        let max_attempts = 30;
        let delay = Duration::from_secs(1);

        for attempt in 1..=max_attempts {
            match self.send_request(WorkerRequest::Health) {
                Ok(WorkerResponse::Health { status, .. }) if status == "ok" => {
                    info!("Worker health check passed on attempt {}", attempt);
                    return Ok(());
                }
                Ok(WorkerResponse::Health { status, .. }) => {
                    warn!("Health check returned status: {} (attempt {})", status, attempt);
                }
                Ok(WorkerResponse::Error { error }) => {
                    warn!("Health check error (attempt {}): {:?}", attempt, error);
                }
                Ok(other) => {
                    warn!("Unexpected response (attempt {}): {:?}", attempt, other);
                }
                Err(e) => {
                    warn!("Health check failed (attempt {}): {}", attempt, e);
                }
            }
            std::thread::sleep(delay);
        }

        Err("Health check timeout after 30 seconds".to_string())
    }

    /// Send JSON-RPC request to worker
    fn send_request(&self, request: WorkerRequest) -> Result<WorkerResponse, String> {
        let mut stdin_guard = self.worker_stdin.lock().unwrap();
        let stdin = stdin_guard.as_mut().ok_or("Worker stdin not available")?;
        
        let json = serde_json::to_string(&request)
            .map_err(|e| format!("Failed to serialize request: {}", e))?;
        
        stdin.write_all(json.as_bytes())
            .map_err(|e| format!("Failed to write to worker: {}", e))?;
        stdin.write_all(b"\n")
            .map_err(|e| format!("Failed to write newline: {}", e))?;
        stdin.flush()
            .map_err(|e| format!("Failed to flush: {}", e))?;
        
        drop(stdin_guard);

        // Read response
        let mut stdout_guard = self.worker_stdout.lock().unwrap();
        let stdout = stdout_guard.as_mut().ok_or("Worker stdout not available")?;
        
        let mut line = String::new();
        stdout.read_line(&mut line)
            .map_err(|e| format!("Failed to read response: {}", e))?;
        
        let response: WorkerResponse = serde_json::from_str(line.trim())
            .map_err(|e| format!("Failed to parse response: {} - line: {}", e, line))?;
        
        Ok(response)
    }

    /// Stop the Laravel backend
    pub fn stop(&self) -> Result<(), String> {
        let mut state = self.state.lock().unwrap();
        if *state == SidecarState::Stopped || *state == SidecarState::Stopping {
            return Ok(());
        }
        *state = SidecarState::Stopping;
        drop(state);

        // Send shutdown command via JSON-RPC
        let _ = self.send_request(WorkerRequest::Shutdown);

        // Send shutdown signal
        if let Some(tx) = self.shutdown_tx.lock().unwrap().take() {
            let _ = tx.send(());
        }

        // Kill process
        if let Some(mut child) = self.worker_process.lock().unwrap().take() {
            info!("Stopping PHP Worker...");
            let _ = child.kill();
            let _ = child.wait();
        }

        *self.state.lock().unwrap() = SidecarState::Stopped;
        info!("Laravel sidecar stopped");
        Ok(())
    }

    /// Monitor the child process
    fn monitor_process(&self, mut shutdown_rx: mpsc::Receiver<()>) {
        let worker_process = self.worker_process.clone();
        let state = self.state.clone();

        std::thread::spawn(move || {
            loop {
                std::thread::sleep(Duration::from_secs(5));
                
                // Check if shutdown requested
                if shutdown_rx.try_recv().is_ok() {
                    info!("Shutdown signal received");
                    break;
                }
                
                // Check PHP Worker process
                let process_ok = {
                    let mut guard = worker_process.lock().unwrap();
                    if let Some(child) = guard.as_mut() {
                        match child.try_wait() {
                            Ok(Some(status)) => {
                                error!("PHP Worker exited with status: {}", status);
                                false
                            }
                            Ok(None) => true, // Still running
                            Err(e) => {
                                error!("Error checking PHP Worker: {}", e);
                                false
                            }
                        }
                    } else {
                        false
                    }
                };

                if !process_ok {
                    *state.lock().unwrap() = SidecarState::Error("Process died unexpectedly".to_string());
                    break;
                }
            }
        });
    }
}