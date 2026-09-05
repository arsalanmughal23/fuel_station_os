use std::fs;
use std::path::PathBuf;

fn main() {
    let manifest_dir = PathBuf::from(env!("CARGO_MANIFEST_DIR"));
    
    // In development, copy backend to resources/backend
    // In production, the backend is already in resources/backend (bundled)
    let backend_src = manifest_dir.join("../../backend");
    let backend_dst = manifest_dir.join("resources/backend");
    
    // Only copy in development when backend exists at ../backend
    if backend_src.exists() && !backend_dst.exists() {
        if backend_dst.exists() {
            fs::remove_dir_all(&backend_dst).ok();
        }
        copy_dir_all(&backend_src, &backend_dst).expect("Failed to copy backend");
        println!("cargo:rerun-if-changed=../backend");
    }
    
    // Ensure public/storage directory exists (for symlink target)
    let storage_dir = manifest_dir.join("resources/backend/public/storage");
    // If it's a symlink, remove it and create directory instead
    if storage_dir.symlink_metadata().is_ok() {
        fs::remove_file(&storage_dir).ok();
    }
    if !storage_dir.exists() {
        fs::create_dir_all(&storage_dir).expect("Failed to create storage directory");
    }
    
    // Copy FrankenPHP config files
    let config_files = ["Caddyfile", "frankenphp-worker.php"];
    for config in config_files {
        let src = manifest_dir.join("../backend").join(config);
        let dst = manifest_dir.join("resources").join(config);
        if src.exists() {
            fs::copy(&src, &dst).expect(&format!("Failed to copy {}", config));
            println!("cargo:rerun-if-changed={}", config);
        }
    }
    
    tauri_build::build()
}

fn copy_dir_all(src: &PathBuf, dst: &PathBuf) -> std::io::Result<()> {
    fs::create_dir_all(dst)?;
    for entry in fs::read_dir(src)? {
        let entry = entry?;
        let ty = entry.file_type()?;
        let name = entry.file_name();
        let name_str = name.to_string_lossy();
        
        // Skip unwanted directories/files
        if name_str == "vendor" 
            || name_str == "node_modules"
            || name_str == ".git"
            || name_str == ".nuxt"
            || name_str == ".output"
            || name_str == "dist"
            || name_str.starts_with(".env")
            || name_str.ends_with(".sqlite")
            || name_str.ends_with(".sqlite-wal")
            || name_str.ends_with(".sqlite-shm") {
            continue;
        }
        
        let path = entry.path();
        let dst_path = dst.join(&name);
        
        if ty.is_dir() {
            if name_str == "storage" {
                copy_storage(&path, &dst_path)?;
            } else {
                copy_dir_all(&path, &dst_path)?;
            }
        } else if ty.is_symlink() {
            // Handle symlinks - copy the target or create the directory
            if let Ok(target) = fs::read_link(&path) {
                // If it's a symlink to a directory, create the directory instead
                if target.is_dir() {
                    fs::create_dir_all(&dst_path)?;
                } else {
                    // For file symlinks, try to copy the target
                    if target.exists() {
                        fs::copy(&target, &dst_path)?;
                    } else {
                        fs::create_dir_all(&dst_path).ok(); // create empty dir for missing targets
                    }
                }
            }
        } else {
            if !name_str.starts_with(".env") && !name_str.ends_with(".sqlite") {
                fs::copy(&path, dst_path)?;
            }
        }
    }
    Ok(())
}

fn copy_storage(src: &PathBuf, dst: &PathBuf) -> std::io::Result<()> {
    fs::create_dir_all(dst)?;
    for entry in fs::read_dir(src)? {
        let entry = entry?;
        let ty = entry.file_type()?;
        let name = entry.file_name();
        let name_str = name.to_string_lossy();
        
        // Skip logs, cache, sessions, views
        if name_str == "logs" 
            || name_str.starts_with("framework") 
            || name_str == "debugbar" {
            continue;
        }
        
        let path = entry.path();
        let dst_path = dst.join(&name);
        
        if ty.is_dir() {
            copy_dir_all(&path, &dst_path)?;
        } else {
            fs::copy(&path, dst_path)?;
        }
    }
    Ok(())
}