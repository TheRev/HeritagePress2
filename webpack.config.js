const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
  ...defaultConfig,
  entry: {
    // Admin scripts
    admin: './assets/js/admin.js',
    // Frontend scripts
    frontend: './assets/js/frontend.js',
    // Block editor scripts
    blocks: './assets/js/blocks.js',
    // Admin styles
    'admin-style': './assets/css/admin.scss',
    // Frontend styles
    'frontend-style': './assets/css/frontend.scss'
  },
  output: {
    path: path.resolve(__dirname, 'dist'),
    filename: '[name].js'
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'assets/js'),
      '@css': path.resolve(__dirname, 'assets/css'),
      '@images': path.resolve(__dirname, 'assets/images')
    }
  }
};
