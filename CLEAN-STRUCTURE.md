# HeritagePress Plugin - Clean Structure

## Files Removed

### Development/Testing Scripts (Removed):

- All `*tng*` development scripts (~15 files)
- All test scripts (`test-*.php`)
- Database comparison and fixing scripts
- Development documentation files (BUILD.md, DATABASE-\*.md, etc.)
- Legacy files (`*.legacy`)
- Development tools (fix-php-paths.ps1, setup-php.bat)
- Test data files

### Files Kept (Production Ready):

#### Core Plugin Files:

```
heritagepress.php                 # Main plugin file
```

#### Includes (Core Classes):

```
includes/
  class-hp-database-manager.php   # Database management (TNG-free)
  class-hp-person-manager.php     # Person management
  class-hp-family-manager.php     # Family management
  class-hp-gedcom-importer.php    # GEDCOM import functionality
```

#### Admin Interface:

```
admin/
  class-hp-admin.php             # Admin functionality
  views/
    dashboard.php                # Admin dashboard
    import.php                   # Import interface
    tables.php                   # Table management
  css/
    admin.css                    # Admin styles
  js/
    admin.js                     # Admin JavaScript
```

#### Public Interface:

```
public/
  class-hp-public.php            # Public functionality
  css/
    public.css                   # Public styles
  js/
    public.js                    # Public JavaScript
```

#### Development/Build Files:

```
.gitignore                       # Git ignore rules
.vscode/                         # VS Code settings
composer.json                    # Composer dependencies
composer.lock                    # Composer lock file
package.json                     # NPM dependencies
webpack.config.js                # Webpack build config
vendor/                          # Composer dependencies
```

#### Documentation:

```
LICENSE                          # License file
README.md                        # Plugin documentation
```

#### TNG Reference (Manual Setup Required):

```
tng-reference/                   # Symbolic link to C:\MAMP\htdocs\tng
                                # (Requires manual creation with admin privileges)
```

**To create the TNG reference symbolic link:**

```powershell
# Run PowerShell as Administrator, then:
New-Item -ItemType SymbolicLink -Path "tng-reference" -Target "C:\MAMP\htdocs\tng"
```

This provides read-only access to the TNG installation for reference purposes.

## Summary

- **Removed**: ~25+ development and testing files
- **Kept**: Only production-ready files needed for plugin functionality
- **Clean**: No TNG references in active code
- **Modular**: Well-organized file structure
- **Complete**: All genealogy functionality preserved

The plugin is now production-ready with a clean, maintainable codebase.
