#!/bin/bash
# Package installer script for FuelStationOS
# Creates platform-specific installers for FrankenPHP sidecar

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
BUILD_DIR="$PROJECT_ROOT/build"

echo "📦 FuelStationOS Packager (FrankenPHP)"
echo "========================="

PLATFORM="${1:-all}"

# Get version from Cargo.toml
VERSION=$(grep '^version' "$PROJECT_ROOT/src-tauri/Cargo.toml" | head -1 | sed 's/version = "\(.*\)"/\1/')
echo "Version: $VERSION"

mkdir -p "$BUILD_DIR/installers"

# Prepare sidecar resources for packaging
prepare_sidecar_resources() {
    echo "📦 Preparing FrankenPHP sidecar resources..."
    SIDECAR_DIR="$BUILD_DIR/installers/sidecar"
    rm -rf "$SIDECAR_DIR"
    mkdir -p "$SIDECAR_DIR"
    
    # Copy backend (optimized for production)
    cp -R "$PROJECT_ROOT/backend" "$SIDECAR_DIR/backend"
    
    # Copy frontend dist
    cp -R "$PROJECT_ROOT/frontend/dist" "$SIDECAR_DIR/dist"
    
    # Copy Caddyfile and worker script
    cp "$PROJECT_ROOT/backend/Caddyfile" "$SIDECAR_DIR/Caddyfile" 2>/dev/null || true
    cp "$PROJECT_ROOT/backend/frankenphp-worker.php" "$SIDECAR_DIR/frankenphp-worker.php" 2>/dev/null || true
    
    # Copy Tauri binary
    cp "$PROJECT_ROOT/src-tauri/target/release/fuel_station_os" "$SIDECAR_DIR/fuel_station_os" 2>/dev/null || true
    cp "$PROJECT_ROOT/src-tauri/target/release/fuel_station_os.exe" "$SIDECAR_DIR/fuel_station_os.exe" 2>/dev/null || true
    
    echo "✅ Sidecar resources prepared at $SIDECAR_DIR"
}

package_windows() {
    echo "📦 Building Windows NSIS installer..."
    
    prepare_sidecar_resources
    
    # Create NSIS script
    cat > "$BUILD_DIR/installers/windows.nsi" << 'EOF'
!include "MUI2.nsh"
!include "FileFunc.nsh"

Name "FuelStationOS"
OutFile "FuelStationOS-Setup-${VERSION}.exe"
InstallDir "$LOCALAPPDATA\FuelStationOS"
InstallDirRegKey HKCU "Software\FuelStationOS" "InstallDir"
RequestExecutionLevel user

!define MUI_ICON "icons/icon.ico"
!define MUI_UNICON "icons/icon.ico"
!insertmacro MUI_PAGE_WELCOME
!insertmacro MUI_PAGE_DIRECTORY
!insertmacro MUI_PAGE_INSTFILES
!insertmacro MUI_PAGE_FINISH
!insertmacro MUI_UNPAGE_WELCOME
!insertmacro MUI_UNPAGE_CONFIRM
!insertmacro MUI_UNPAGE_INSTFILES
!insertmacro MUI_UNPAGE_FINISH
!insertmacro MUI_LANGUAGE "English"

Section "Main"
  SetOutPath "$INSTDIR"
  
  ; Copy sidecar resources (backend + frontend + FrankenPHP config)
  File /r "sidecar\*"
  
  ; Create shortcuts
  CreateDirectory "$SMPROGRAMS\FuelStationOS"
  CreateShortcut "$SMPROGRAMS\FuelStationOS\FuelStationOS.lnk" "$INSTDIR\fuel_station_os.exe"
  CreateShortcut "$DESKTOP\FuelStationOS.lnk" "$INSTDIR\fuel_station_os.exe"
  
  ; Database directory
  CreateDirectory "$APPDATA\FuelStationOS\database"
  
  WriteRegStr HKCU "Software\FuelStationOS" "InstallDir" "$INSTDIR"
  WriteRegStr HKCU "Software\FuelStationOS" "Version" "${VERSION}"
SectionEnd

Section "Uninstall"
  Delete "$INSTDIR\*"
  RMDir /r "$INSTDIR"
  Delete "$SMPROGRAMS\FuelStationOS\*"
  RMDir "$SMPROGRAMS\FuelStationOS"
  Delete "$DESKTOP\FuelStationOS.lnk"
  DeleteRegKey HKCU "Software\FuelStationOS"
SectionEnd

Function .onInit
  FindWindow $0 "FuelStationOS"
  StrCmp $0 0 +3
  MessageBox MB_OK|MB_ICONEXCLAMATION "FuelStationOS is running. Please close it first."
  Abort
FunctionEnd
EOF

    # Compile NSIS installer (requires NSIS installed)
    if command -v makensis &> /dev/null; then
        cd "$BUILD_DIR/installers"
        makensis windows.nsi
        echo "✅ Windows installer created: FuelStationOS-Setup-${VERSION}.exe"
    else
        echo "⚠️ NSIS not installed. Skipping Windows installer."
    fi
}

package_macos() {
    echo "📦 Building macOS DMG..."
    
    if [[ "$OSTYPE" == "darwin"* ]]; then
        prepare_sidecar_resources
        
        # Create app bundle
        APP_NAME="FuelStationOS.app"
        APP_DIR="$BUILD_DIR/installers/$APP_NAME"
        
        mkdir -p "$APP_DIR/Contents/MacOS"
        mkdir -p "$APP_DIR/Contents/Resources"
        mkdir -p "$APP_DIR/Contents/Frameworks"
        
        # Copy binary
        cp "$PROJECT_ROOT/src-tauri/target/release/fuel_station_os" "$APP_DIR/Contents/MacOS/FuelStationOS"
        chmod +x "$APP_DIR/Contents/MacOS/FuelStationOS"
        
        # Copy sidecar resources
        cp -R "$BUILD_DIR/installers/sidecar" "$APP_DIR/Contents/Resources/sidecar"
        
        # Create Info.plist
        cat > "$APP_DIR/Contents/Info.plist" << EOF
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>CFBundleExecutable</key>
    <string>FuelStationOS</string>
    <key>CFBundleIdentifier</key>
    <string>com.fuelstation.os</string>
    <key>CFBundleName</key>
    <string>FuelStationOS</string>
    <key>CFBundleVersion</key>
    <string>${VERSION}</string>
    <key>CFBundleShortVersionString</key>
    <string>${VERSION}</string>
    <key>CFBundleIconFile</key>
    <string>icon.icns</string>
    <key>LSMinimumSystemVersion</key>
    <string>10.15</string>
    <key>NSHighResolutionCapable</key>
    <true/>
    <key>NSHumanReadableCopyright</key>
    <string>Copyright © 2024 FuelStationOS Team</string>
</dict>
</plist>
EOF
        
        # Copy icon
        cp "$PROJECT_ROOT/src-tauri/icons/icon.icns" "$APP_DIR/Contents/Resources/icon.icns" 2>/dev/null || true
        
        # Create DMG
        hdiutil create -volname "FuelStationOS" -srcfolder "$APP_DIR" -ov -format UDZO "$BUILD_DIR/installers/FuelStationOS-${VERSION}.dmg"
        
        echo "✅ macOS DMG created: FuelStationOS-${VERSION}.dmg"
    else
        echo "⚠️ Not on macOS. Skipping DMG creation."
    fi
}

package_linux() {
    echo "📦 Building Linux packages..."
    
    if [[ "$OSTYPE" == "linux-gnu"* ]]; then
        prepare_sidecar_resources
        
        # AppImage
        APPIMAGE_DIR="$BUILD_DIR/installers/FuelStationOS.AppDir"
        mkdir -p "$APPIMAGE_DIR/usr/bin"
        mkdir -p "$APPIMAGE_DIR/usr/share/applications"
        mkdir -p "$APPIMAGE_DIR/usr/share/icons/hicolor/256x256/apps"
        mkdir -p "$APPIMAGE_DIR/opt/fuel_station_os"
        
        # Copy binary
        cp "$PROJECT_ROOT/src-tauri/target/release/fuel_station_os" "$APPIMAGE_DIR/usr/bin/fuel_station_os"
        chmod +x "$APPIMAGE_DIR/usr/bin/fuel_station_os"
        
        # Copy sidecar resources
        cp -R "$BUILD_DIR/installers/sidecar" "$APPIMAGE_DIR/opt/fuel_station_os/sidecar"
        
        # Desktop entry
        cat > "$APPIMAGE_DIR/usr/share/applications/fuel_station_os.desktop" << EOF
[Desktop Entry]
Type=Application
Name=FuelStationOS
Exec=fuel_station_os
Icon=fuel_station_os
Terminal=false
Categories=Utility;
EOF
        
        # Icon
        cp "$PROJECT_ROOT/src-tauri/icons/icon.png" "$APPIMAGE_DIR/usr/share/icons/hicolor/256x256/apps/fuel_station_os.png" 2>/dev/null || true
        
        # AppRun - sets up the sidecar path
        cat > "$APPIMAGE_DIR/AppRun" << 'EOF'
#!/bin/bash
HERE="$(dirname "$(readlink -f "${0}")")"
export LD_LIBRARY_PATH="${HERE}/usr/lib:${LD_LIBRARY_PATH}"
# Set sidecar path for the Tauri app
export FUEL_STATION_OS_SIDECAR="${HERE}/opt/fuel_station_os/sidecar"
exec "${HERE}/usr/bin/fuel_station_os" "$@"
EOF
        chmod +x "$APPIMAGE_DIR/AppRun"
        
        # Create AppImage
        if command -v appimagetool &> /dev/null; then
            appimagetool "$APPIMAGE_DIR" "$BUILD_DIR/installers/FuelStationOS-${VERSION}-x86_64.AppImage"
            echo "✅ AppImage created"
        else
            echo "⚠️ appimagetool not installed. Skipping AppImage."
        fi
        
        # DEB package
        if command -v dpkg-deb &> /dev/null; then
            DEB_DIR="$BUILD_DIR/installers/fuel-station-os_${VERSION}_amd64"
            mkdir -p "$DEB_DIR/DEBIAN"
            mkdir -p "$DEB_DIR/opt/fuel_station_os"
            mkdir -p "$DEB_DIR/usr/bin"
            mkdir -p "$DEB_DIR/usr/share/applications"
            mkdir -p "$DEB_DIR/usr/share/icons/hicolor/256x256/apps"
            
            cp -R "$BUILD_DIR/installers/sidecar" "$DEB_DIR/opt/fuel_station_os/sidecar"
            cp "$PROJECT_ROOT/src-tauri/target/release/fuel_station_os" "$DEB_DIR/usr/bin/fuel_station_os"
            
            cat > "$DEB_DIR/DEBIAN/control" << EOF
Package: fuel-station-os
Version: ${VERSION}
Section: utils
Priority: optional
Architecture: amd64
Depends: libwebkit2gtk-4.1-0, libssl3, libsqlite3-0, libgtk-3-0, libayatana-appindicator3-1, php8.3-fpm, nginx, frankenphp
Maintainer: FuelStationOS Team <team@fuelstationos.com>
Description: Fuel Station Management System
 Desktop application for managing fuel station operations.
EOF
            
            cat > "$DEB_DIR/usr/share/applications/fuel-station-os.desktop" << EOF
[Desktop Entry]
Type=Application
Name=FuelStationOS
Exec=fuel_station_os
Icon=fuel_station_os
Terminal=false
Categories=Utility;
EOF
            
            cp "$PROJECT_ROOT/src-tauri/icons/icon.png" "$DEB_DIR/usr/share/icons/hicolor/256x256/apps/fuel_station_os.png" 2>/dev/null || true
            
            dpkg-deb --build "$DEB_DIR" "$BUILD_DIR/installers/fuel-station-os_${VERSION}_amd64.deb"
            echo "✅ DEB package created"
        fi
        
        echo "✅ Linux packages created"
    else
        echo "⚠️ Not on Linux. Skipping Linux packages."
    fi
}

case "$PLATFORM" in
    windows)
        package_windows
        ;;
    macos)
        package_macos
        ;;
    linux)
        package_linux
        ;;
    all)
        package_windows
        package_macos
        package_linux
        ;;
    *)
        echo "Usage: $0 [windows|macos|linux|all]"
        exit 1
        ;;
esac

echo ""
echo "✅ Packaging complete!"
echo "Installers available in: $BUILD_DIR/installers/"
ls -la "$BUILD_DIR/installers/"