import { invoke } from '@tauri-apps/api/core'

/**
 * Composable for Tauri-specific functionality.
 * Provides access to Tauri commands for database backup/restore,
 * system info, and Laravel sidecar management.
 */
export function useTauri() {
  /**
   * Check if running inside Tauri
   */
  const isTauri = import.meta.env.TAURI === 'true' || typeof window !== 'undefined' && window.__TAURI__

  /**
   * Backup the database using Tauri command
   * @returns Path to the backup file
   */
  async function backupDatabase(): Promise<string> {
    if (!isTauri) {
      throw new Error('backupDatabase is only available in Tauri environment')
    }
    return await invoke('backup_database')
  }

  /**
   * Restore the database from a backup file
   * @param backupPath - Path to the backup file
   */
  async function restoreDatabase(backupPath: string): Promise<void> {
    if (!isTauri) {
      throw new Error('restoreDatabase is only available in Tauri environment')
    }
    return await invoke('restore_database', { backupPath })
  }

  /**
   * Get system information (OS, architecture, etc.)
   */
  async function getSystemInfo(): Promise<{
    os: string
    arch: string
    version: string
    hostname: string
    cpus: number
    totalMemory: number
    freeMemory: number
  }> {
    if (!isTauri) {
      throw new Error('getSystemInfo is only available in Tauri environment')
    }
    return await invoke('get_system_info')
  }

  /**
   * Start the Laravel sidecar process
   */
  async function startLaravelSidecar(): Promise<void> {
    if (!isTauri) {
      throw new Error('startLaravelSidecar is only available in Tauri environment')
    }
    return await invoke('start_laravel_sidecar')
  }

  /**
   * Stop the Laravel sidecar process
   */
  async function stopLaravelSidecar(): Promise<void> {
    if (!isTauri) {
      throw new Error('stopLaravelSidecar is only available in Tauri environment')
    }
    return await invoke('stop_laravel_sidecar')
  }

  return {
    isTauri,
    backupDatabase,
    restoreDatabase,
    getSystemInfo,
    startLaravelSidecar,
    stopLaravelSidecar,
  }
}