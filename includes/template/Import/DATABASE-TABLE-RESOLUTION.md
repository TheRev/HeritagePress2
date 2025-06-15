# Database Table Issue Resolution

## Issue: Missing Import Jobs Table

**Error:** `Table 'wordpress.wp_hp_import_jobs' doesn't exist`

This error occurs when the import jobs table wasn't created during plugin activation. This can happen if:

1. The plugin was updated and the new table wasn't created
2. There was a database connection issue during activation
3. The table creation failed due to permissions

## Resolution Methods

### Method 1: Reactivate the Plugin (Recommended)

1. Go to **Plugins** → **Installed Plugins** in WordPress admin
2. **Deactivate** HeritagePress
3. **Activate** HeritagePress again
4. The table should be created automatically

### Method 2: Manual Database Creation

If reactivation doesn't work, you can create the table manually:

```sql
CREATE TABLE `wp_hp_import_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` varchar(36) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `file_path` text NOT NULL,
  `import_options` longtext,
  `status` varchar(20) NOT NULL DEFAULT 'queued',
  `progress` decimal(5,2) NOT NULL DEFAULT 0.00,
  `total_records` int(11) NOT NULL DEFAULT 0,
  `processed_records` int(11) NOT NULL DEFAULT 0,
  `errors` longtext,
  `log` longtext,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_id` (`job_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

**Note:** Replace `wp_` with your actual WordPress table prefix if different.

### Method 3: Check Plugin Logs

Look for any error messages in your WordPress debug log that might indicate why the table creation failed.

## Prevention

To prevent this issue in the future:

1. Always backup your database before plugin updates
2. Ensure proper database permissions for the WordPress user
3. Check that your hosting has sufficient storage space
4. Monitor plugin activation for any error messages

## Verification

After resolving the issue, verify the table exists by:

1. Going to **HeritagePress** → **Import/Export**
2. The "Recent Import Jobs" section should display without errors
3. Try uploading a small GEDCOM file to test the background processing

## Support

If the issue persists after trying these methods:

1. Check your hosting provider's database permissions
2. Contact your hosting support for database access issues
3. Consider temporarily switching to a different WordPress user with full database privileges

The import/export functionality will work normally once the table is created.
