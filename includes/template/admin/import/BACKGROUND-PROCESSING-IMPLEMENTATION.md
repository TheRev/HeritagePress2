# Background Processing Implementation Documentation

## Overview

This document describes the implementation of background processing for GEDCOM imports in HeritagePress, providing a robust solution for handling large file uploads and imports without browser timeouts.

## Components Implemented

### 1. Background Import Queue System

#### Database Table: `wp_hp_import_jobs`

```sql
CREATE TABLE wp_hp_import_jobs (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    job_id varchar(36) NOT NULL,
    user_id bigint(20) NOT NULL,
    file_path text NOT NULL,
    import_options longtext,
    status varchar(20) DEFAULT 'queued',
    progress int(3) DEFAULT 0,
    total_records int(11) DEFAULT 0,
    processed_records int(11) DEFAULT 0,
    errors longtext,
    log longtext,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY job_id (job_id),
    KEY user_id (user_id),
    KEY status (status)
);
```

#### Status Values

- `queued` - Job is waiting to be processed
- `processing` - Job is currently being processed
- `completed` - Job finished successfully
- `failed` - Job encountered an error
- `cancelled` - Job was cancelled by user

### 2. Background Processing Methods (`class-hp-admin.php`)

#### Core Methods:

- `queue_gedcom_import()` - Creates import job and schedules processing
- `process_gedcom_import_background()` - Main background processing handler
- `process_gedcom_file()` - Processes GEDCOM with progress tracking
- `count_gedcom_records()` - Pre-processes file to count total records
- `process_gedcom_record()` - Handles individual GEDCOM records
- `update_import_job_status()` - Updates job progress and status

#### Progress Tracking:

- 0-5%: Job queued and starting
- 5-10%: Analyzing GEDCOM file structure
- 10-95%: Processing records with batch updates
- 95-100%: Running post-import utilities

### 3. AJAX Handlers

#### `wp_ajax_hp_get_import_status`

Returns current job status including:

- Current status and progress percentage
- Total and processed record counts
- Detailed log messages
- Timestamps

#### `wp_ajax_hp_cancel_import`

Allows users to cancel running imports

### 4. Import Status UI (`import-status.php`)

#### Features:

- Real-time progress monitoring with auto-refresh
- Visual progress bar with animated fill
- Status indicators with color coding
- Detailed import log display
- Cancel import functionality
- Navigation back to import form

#### JavaScript Polling:

- Updates status every 2 seconds for active jobs
- Stops polling when job completes/fails/cancels
- Handles connection errors gracefully

### 5. Recent Imports List

#### Display Features:

- Shows last 10 import jobs for current user
- Mini progress bars for quick status view
- Color-coded status badges
- Direct links to detailed job status
- Responsive table design

### 6. Email Notifications

#### Completion Emails:

- Success notification with record count
- Failure notification with error details
- Sent to job owner's email address

### 7. Cleanup System

#### Automatic Cleanup:

- Daily cron job removes old completed jobs
- Keeps jobs for 30 days after completion
- Prevents database bloat

## File Structure

```
includes/template/Import/
├── import.php              # Main import form with recent jobs list
├── import-status.php       # Job status monitoring page
├── import-export.css       # Enhanced styles for status UI
└── BACKGROUND-PROCESSING-IMPLEMENTATION.md
```

## Integration Points

### 1. Import Form Handler

When a GEDCOM import is submitted:

1. File is processed through chunked upload system
2. Import job is queued with `queue_gedcom_import()`
3. User is redirected to status page with job ID
4. Background processing begins immediately

### 2. Status Page Access

- URL: `admin.php?page=heritagepress-import&tab=import&job_id={job_id}`
- Automatically loaded when job_id parameter is present
- Shows real-time status for specific job

### 3. WordPress Cron Integration

- Background processing uses `wp_schedule_single_event()`
- Cleanup uses `wp_schedule_event()` with daily recurrence
- No external cron configuration required

## Security Features

### 1. User Authorization

- Jobs are user-specific and isolated
- AJAX requests verify user capabilities
- Nonce verification for all requests

### 2. File Security

- GEDCOM files stored in secure upload directory
- File path validation and sanitization
- Cleanup of processed files

## Error Handling

### 1. Job-Level Errors

- Exceptions caught and logged to job record
- User notified via email and status page
- Detailed error messages for debugging

### 2. System-Level Errors

- PHP errors logged to WordPress error log
- Memory and time limits increased for large files
- Graceful degradation on system constraints

## Performance Optimizations

### 1. Batch Processing

- Records processed in configurable batches (default: 100)
- Progress updated per batch to reduce database writes
- Memory management with file streaming

### 2. Database Efficiency

- Indexed job table for fast lookups
- Prepared statements for all queries
- Minimal data stored per update

## Configuration Options

### 1. Processing Settings

- Batch size: 100 records per update
- Progress update frequency: Per batch
- Memory limit: 512MB for imports
- Time limit: Unlimited for background jobs

### 2. Cleanup Settings

- Job retention: 30 days
- Cleanup frequency: Daily
- Cleanup scope: Completed/failed/cancelled jobs only

## Usage Flow

### 1. Normal Import Process

```
User selects file → Chunked upload → Job queued →
Redirect to status page → Background processing →
Progress updates → Completion notification
```

### 2. Status Monitoring

```
Status page loads → JavaScript polling starts →
AJAX requests for updates → UI updates in real-time →
Polling stops on completion
```

### 3. Job Management

```
Recent jobs list → Click view → Status page →
Real-time monitoring → Post-import utilities
```

## Testing Recommendations

### 1. Small File Test

- Upload small GEDCOM (< 1MB)
- Verify quick processing and completion
- Check email notifications

### 2. Large File Test

- Upload large GEDCOM (> 50MB)
- Monitor progress updates
- Verify no browser timeouts

### 3. Cancellation Test

- Start import and cancel mid-process
- Verify job stops and status updates
- Check cleanup of partial data

### 4. Multiple Jobs Test

- Queue multiple imports simultaneously
- Verify proper isolation and processing
- Check recent jobs list display

## Future Enhancements

### 1. Parallel Processing

- Support for multiple concurrent import jobs
- Queue management with priority levels
- Resource-based throttling

### 2. Advanced Progress Tracking

- Step-by-step processing breakdown
- Estimated completion times
- Performance metrics

### 3. Import Validation

- Pre-import GEDCOM validation
- Structure analysis and warnings
- Data quality reports

### 4. Resume Capability

- Checkpoint-based resuming for failed jobs
- Partial import recovery
- Incremental processing options

## Conclusion

The background processing implementation provides a robust, scalable solution for GEDCOM imports that:

- Eliminates browser timeout issues
- Provides real-time progress feedback
- Handles large files efficiently
- Maintains system performance
- Offers excellent user experience

The system is fully integrated with WordPress standards, includes comprehensive error handling, and provides multiple monitoring and management interfaces for optimal usability.
