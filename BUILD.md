# HeritagePress Development Setup

## 🏗️ Build Process

Since MAMP's PHP lacks SSL extensions, we'll use a simpler development approach:

### Development Dependencies (Manual Setup)

- **PHP CodeSniffer**: For WordPress coding standards
- **PHPUnit**: For testing
- **Webpack**: For asset compilation

### Build Commands

#### For Development:

```bash
# Development assets
npm run dev

# Watch for changes
npm run dev --watch
```

#### For Production:

```bash
# Build optimized assets
npm run build

# Create production plugin zip
npm run build:production
```

### Plugin Structure (Production Ready)

The final WordPress plugin will be completely standalone:

- ✅ No vendor dependencies
- ✅ All assets compiled
- ✅ WordPress-compatible autoloader
- ✅ Production-optimized code

### Development vs Production

**Development Mode:**

- Composer for code quality tools
- NPM for asset compilation
- Source maps for debugging
- Unminified assets

**Production Mode:**

- No external dependencies
- Minified/optimized assets
- WordPress autoloader only
- Standalone plugin files

## 🎯 Next Steps

1. Set up basic plugin structure
2. Create database schema
3. Build GEDCOM import system
4. Implement core genealogy features

The plugin will be **100% independent** when distributed!
