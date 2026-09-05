// Tauri Commands Module
// Provides commands that match frontend useTauri composable expectations

use std::path::PathBuf;
use tauri::{AppHandle, Emitter, Runtime, State, Manager};
use tauri_plugin_dialog::DialogExt;
use crate::sidecar::{LaravelSidecar, SidecarState};

/// Error type for commands
#[derive(Debug, thiserror::Error)]
pub enum CommandError {
    #[error("IO error: {0}")]
    Io(#[from] std::io::Error),
    #[error("Dialog error: {0}")]
    Dialog(#[from] tauri_plugin_dialog::Error),
    #[error("Sidecar error: {0}")]
    Sidecar(String),
    #[error("Database not found")]
    DatabaseNotFound,
    #[error("Serialization error: {0}")]
    Serde(#[from] serde_json::Error),
}

impl serde::Serialize for CommandError {
    fn serialize<S>(&self, serializer: S) -> Result<S::Ok, S::Error>
    where
        S: serde::Serializer,
    {
        serializer.serialize_str(self.to_string().as_ref())
    }
}

type CommandResult<T> = std::result::Result<T, CommandError>;

/// System information structure
#[derive(Debug, Clone, serde::Serialize, serde::Deserialize)]
pub struct SystemInfo {
    pub os: String,
    pub arch: String,
    pub version: String,
    pub hostname: String,
    pub cpus: usize,
    pub total_memory: u64,
    pub free_memory: u64,
}

/// Sidecar status for frontend
#[derive(Debug, Clone, serde::Serialize, serde::Deserialize)]
pub struct SidecarStatus {
    pub running: bool,
    pub state: String,
    pub started_at: Option<String>,
}

/// Get database path
fn get_database_path(app: &AppHandle) -> CommandResult<PathBuf> {
    let app_data_dir = app
        .path()
        .app_data_dir()
        .map_err(|e| CommandError::Io(std::io::Error::other(e)))?;

    std::fs::create_dir_all(&app_data_dir)?;
    Ok(app_data_dir.join("fuel_station.sqlite"))
}

/// Start Laravel sidecar (frontend-compatible name)
#[tauri::command]
pub async fn start_laravel_sidecar<R: Runtime>(
    app: AppHandle<R>,
    sidecar: State<'_, LaravelSidecar>,
) -> CommandResult<String> {
    let state = sidecar.state();
    if state != SidecarState::Stopped {
        return Err(CommandError::Sidecar("Sidecar is already running or starting".to_string()));
    }

    sidecar.start().map_err(CommandError::Sidecar)?;
    
    // Notify frontend
    app.emit("sidecar-started", ()).ok();
    
    Ok("Laravel sidecar started".to_string())
}

/// Stop Laravel sidecar (frontend-compatible name)
#[tauri::command]
pub async fn stop_laravel_sidecar<R: Runtime>(
    app: AppHandle<R>,
    sidecar: State<'_, LaravelSidecar>,
) -> CommandResult<String> {
    let state = sidecar.state();
    if state == SidecarState::Stopped {
        return Err(CommandError::Sidecar("Sidecar is not running".to_string()));
    }

    sidecar.stop().map_err(CommandError::Sidecar)?;
    
    // Notify frontend
    app.emit("sidecar-stopped", ()).ok();
    
    Ok("Laravel sidecar stopped".to_string())
}

/// Get sidecar status (frontend-compatible name)
#[tauri::command]
pub async fn get_sidecar_status(
    sidecar: State<'_, LaravelSidecar>,
) -> CommandResult<SidecarStatus> {
    let state = sidecar.state();
    Ok(SidecarStatus {
        running: state != SidecarState::Stopped,
        state: format!("{:?}", state),
        started_at: None,
    })
}

/// Backup database
#[tauri::command]
pub async fn backup_database(app: AppHandle) -> CommandResult<String> {
    let db_path = get_database_path(&app)?;

    if !db_path.exists() {
        return Err(CommandError::DatabaseNotFound);
    }

    // Get backup file path from user (synchronous API for dialog)
    let _backup_path = app
        .dialog()
        .file()
        .add_filter("SQLite Database", &["sqlite", "db", "sqlite3"])
        .set_file_name(&format!(
            "fuel_station_backup_{}.sqlite",
            chrono::Utc::now().format("%Y%m%d_%H%M%S")
        ))
        .save_file(move |file_path| {
            if let Some(path) = file_path {
                // Copy database file in callback
                let db_path = db_path.clone();
                if let Ok(backup_path) = path.into_path() {
                    std::thread::spawn(move || {
                        let _ = std::fs::copy(db_path, backup_path);
                    });
                }
            }
        });

    // Return immediately, callback handles the copy
    Ok("Backup dialog opened".to_string())
}

/// Restore database
#[tauri::command]
pub async fn restore_database(app: AppHandle, backup_path: String) -> CommandResult<()> {
    let db_path = get_database_path(&app)?;
    let backup = PathBuf::from(backup_path);

    if !backup.exists() {
        return Err(CommandError::DatabaseNotFound);
    }

    // Copy backup to database location
    std::fs::copy(&backup, &db_path)?;

    // Notify frontend to refresh
    app.emit("database-restored", ()).ok();

    Ok(())
}

/// Get system information
#[tauri::command]
pub async fn get_system_info() -> CommandResult<SystemInfo> {
    let sys = sysinfo::System::new_all();

    Ok(SystemInfo {
        os: sysinfo::System::name().unwrap_or_else(|| "Unknown".to_string()),
        arch: std::env::consts::ARCH.to_string(),
        version: sysinfo::System::os_version().unwrap_or_else(|| "Unknown".to_string()),
        hostname: sysinfo::System::host_name().unwrap_or_else(|| "Unknown".to_string()),
        cpus: sys.cpus().len(),
        total_memory: sys.total_memory(),
        free_memory: sys.free_memory(),
    })
}