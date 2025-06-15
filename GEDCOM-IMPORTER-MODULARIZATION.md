# HeritagePress GEDCOM Importer Modularization

## Overview

The original monolithic `HP_GEDCOM_Importer` class has been refactored into a modular system with specialized classes for different aspects of GEDCOM import. This makes the code more maintainable, testable, and extensible.

## Directory Structure

```
includes/
├── class-hp-gedcom-importer-adapter.php   # Adapter for backward compatibility
└── gedcom/                                # New modular GEDCOM import system
    ├── class-hp-gedcom-importer.php       # Main controller class
    ├── class-hp-gedcom-parser.php         # GEDCOM parsing logic
    ├── class-hp-gedcom-validator.php      # GEDCOM validation
    ├── class-hp-gedcom-program-detector.php # Program detection
    ├── class-hp-gedcom-utils.php          # Utility functions
    └── records/                          # Record handlers
        ├── class-hp-gedcom-record-base.php  # Base record handler
        ├── class-hp-gedcom-individual.php   # Individual record handler
        ├── class-hp-gedcom-family.php       # Family record handler
        ├── class-hp-gedcom-source.php       # Source record handler
        ├── class-hp-gedcom-media.php        # Media record handler
        ├── class-hp-gedcom-repository.php   # Repository record handler
        └── class-hp-gedcom-note.php         # Note record handler
```

## Class Responsibilities

### HP_GEDCOM_Importer (Modern)

- Acts as the main controller class
- Delegates to specialized classes
- Coordinates the overall import process
- Manages configuration and statistics

### HP_GEDCOM_Parser

- Handles the parsing of GEDCOM files
- Converts raw GEDCOM lines into structured data
- Builds record hierarchies

### HP_GEDCOM_Validator

- Validates GEDCOM files for structure and content
- Ensures compliance with GEDCOM 5.5.1 standard
- Provides validation errors and warnings

### HP_GEDCOM_Program_Detector

- Analyzes GEDCOM headers and content
- Detects the source genealogy program
- Provides program-specific handling information

### HP_GEDCOM_Utils

- Provides utility functions for date parsing
- Handles common conversion tasks
- Manages character encoding

### Record Handlers

- Base class: `HP_GEDCOM_Record_Base`

  - Common record handling functionality
  - Database interaction utilities
  - Tree management

- Specialized record handlers for:
  - Individuals (INDI)
  - Families (FAM)
  - Sources (SOUR)
  - Media Objects (OBJE)
  - Repositories (REPO)
  - Notes (NOTE)

## Adapter Pattern

The original `HP_GEDCOM_Importer` class has been converted to an adapter that maintains the same public interface while delegating to the new modular system. This ensures backward compatibility with any code that might be using the original class.

## Benefits of Modularization

1. **Maintainability**: Smaller, focused classes are easier to understand and maintain
2. **Testability**: Classes with single responsibilities are easier to test
3. **Extensibility**: New record types can be added without modifying existing code
4. **Separation of concerns**: Each class has a clear, distinct responsibility
5. **Program compatibility**: Better support for different GEDCOM variants through program detection

## Using the New System

To import a GEDCOM file:

```php
// Using the adapter (backward compatibility)
$importer = new HP_GEDCOM_Importer($file_path, $tree_id, $options);
$result = $importer->import();

// Using the new modular system directly
$importer = new HP_GEDCOM_Importer($file_path, $tree_id, $options);
$result = $importer->import();
```

The two approaches yield identical results, ensuring a smooth transition to the new architecture.

## Future Improvements

1. Add more specialized record handlers for other GEDCOM record types
2. Enhance media handling for program-specific media structures
3. Add more robust error handling and recovery mechanisms
4. Implement batch processing for very large GEDCOM files
