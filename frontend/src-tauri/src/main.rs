// Prevents additional console window on Windows in release
#![cfg_attr(not(debug_assertions), windows_subsystem = "windows")]

mod sidecar;
mod commands;

use sidecar::{LaravelSidecar, SidecarConfig};
use tauri::Manager;

fn main() {
    tracing_subscriber::fmt::init();

    let sidecar = LaravelSidecar::new(SidecarConfig::default());

    tauri::Builder::default()
        .plugin(tauri_plugin_shell::init())
        .plugin(tauri_plugin_fs::init())
        .plugin(tauri_plugin_process::init())
        .manage(sidecar)
        .invoke_handler(tauri::generate_handler![
            commands::start_laravel_sidecar,
            commands::stop_laravel_sidecar,
            commands::get_sidecar_status,
            commands::backup_database,
            commands::restore_database,
            commands::get_system_info,
        ])
        .setup(|app| {
            #[cfg(not(debug_assertions))]
            {
                // Use Tauri's async runtime to start sidecar after app is running
                let app_handle = app.handle().clone();
                tauri::async_runtime::spawn(async move {
                    tokio::time::sleep(tokio::time::Duration::from_millis(1000)).await;
                    if let Some(sidecar) = app_handle.try_state::<LaravelSidecar>() {
                        let _ = sidecar.inner().start();
                    }
                });
            }
            Ok(())
        })
        .run(tauri::generate_context!())
        .expect("error while running tauri application");
}