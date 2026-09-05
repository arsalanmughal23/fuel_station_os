use std::process::{Command, Child};
use std::path::PathBuf;
use tauri::AppHandle;

pub struct Services {
    php_fpm: Option<Child>,
    nginx: Option<Child>,
}

impl Services {
    pub fn new() -> Self {
        Self {
            php_fpm: None,
            nginx: None,
        }
    }

    pub fn start(&mut self, app: &AppHandle) -> Result<(), String> {
        let is_dev = cfg!(debug_assertions);
        
        // Determine binary paths based on environment
        let php_fpm_path = if is_dev {
            // Development: use system PHP-FPM
            "/usr/sbin/php-fpm8.3".to_string()
        } else {
            // Production: use bundled PHP-FPM
            self.get_bundled_path(app, "php-fpm")?
        };

        let nginx_path = if is_dev {
            "/usr/sbin/nginx".to_string()
        } else {
            self.get_bundled_path(app, "nginx")?
        };

        // Start services
        self.start_php_fpm(&php_fpm_path, app)?;
        self.start_nginx(&nginx_path, app)?;
        
        Ok(())
    }

    fn get_bundled_path(&self, app: &AppHandle, binary: &str) -> Result<String, String> {
        let target = if cfg!(target_os = "linux") { "linux" } 
                    else if cfg!(target_os = "windows") { "windows" }
                    else { "macos" };
        
        let path = app.path()
            .resolve(&format!("bin/{}/{}", target, binary), 
                    tauri::path::BaseDirectory::Resource)
            .map_err(|e| e.to_string())?;
        
        Ok(path.to_str().unwrap().to_string())
    }

    fn start_php_fpm(&mut self, path: &str, app: &AppHandle) -> Result<(), String> {
        let config = app.path()
            .resolve("php-fpm.conf", tauri::path::BaseDirectory::Resource)
            .map_err(|e| e.to_string())?;

        self.php_fpm = Some(
            Command::new(path)
                .arg("-c")
                .arg(config)
                .arg("-D")
                .spawn()
                .map_err(|e| format!("Failed to start PHP-FPM: {}", e))?
        );
        Ok(())
    }

    fn start_nginx(&mut self, path: &str, app: &AppHandle) -> Result<(), String> {
        let config = app.path()
            .resolve("nginx.conf", tauri::path::BaseDirectory::Resource)
            .map_err(|e| e.to_string())?;

        self.nginx = Some(
            Command::new(path)
                .arg("-c")
                .arg(config)
                .spawn()
                .map_err(|e| format!("Failed to start Nginx: {}", e))?
        );
        Ok(())
    }

    pub fn stop(&mut self) {
        if let Some(mut child) = self.php_fpm.take() {
            let _ = child.kill();
        }
        if let Some(mut child) = self.nginx.take() {
            let _ = child.kill();
        }
    }
}