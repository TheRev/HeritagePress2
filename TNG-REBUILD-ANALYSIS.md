# TNG to HeritagePress Database Mapping Analysis

## Critical Structure Differences Found

### Primary Key Strategy

- **TNG**: Uses string-based IDs (personID VARCHAR(22), familyID VARCHAR(22))
- **HeritagePress**: Uses integer AUTO_INCREMENT IDs
- **Impact**: Complete incompatibility for direct data import

### Field Naming Conventions

- **TNG**: Uses `firstname`, `lastname`, `birthdate`, `birthplace`
- **HeritagePress**: Uses `first_name`, `last_name`, `birth_date`, `birth_place`
- **Impact**: Field mapping required for all operations

### Required TNG-Compatible Rebuild

## Table-by-Table Comparison

### 1. PEOPLE TABLE

#### TNG Structure (from tabledefs.php):

```sql
CREATE TABLE people (
    ID INT NOT NULL AUTO_INCREMENT,
    gedcom VARCHAR(20) NOT NULL,
    personID VARCHAR(22) NOT NULL,
    lnprefix VARCHAR(25) NOT NULL,
    lastname VARCHAR(127) NOT NULL,
    firstname VARCHAR(127) NOT NULL,
    birthdate VARCHAR(50) NOT NULL,
    birthdatetr DATE NOT NULL,
    sex VARCHAR(25) NOT NULL,
    birthplace TEXT NOT NULL,
    deathdate VARCHAR(50) NOT NULL,
    deathdatetr DATE NOT NULL,
    deathplace TEXT NOT NULL,
    altbirthtype VARCHAR(5) NOT NULL,
    altbirthdate VARCHAR(50) NOT NULL,
    altbirthdatetr DATE NOT NULL,
    altbirthplace TEXT NOT NULL,
    burialdate VARCHAR(50) NOT NULL,
    burialdatetr DATE NOT NULL,
    burialplace TEXT NOT NULL,
    burialtype TINYINT NOT NULL,
    baptdate VARCHAR(50) NOT NULL,
    baptdatetr DATE NOT NULL,
    baptplace TEXT NOT NULL,
    confdate VARCHAR(50) NOT NULL,
    confdatetr DATE NOT NULL,
    confplace TEXT NOT NULL,
    initdate VARCHAR(50) NOT NULL,
    initdatetr DATE NOT NULL,
    initplace TEXT NOT NULL,
    endldate VARCHAR(50) NOT NULL,
    endldatetr DATE NOT NULL,
    endlplace TEXT NOT NULL,
    changedate DATETIME NOT NULL,
    nickname TEXT NOT NULL,
    title TINYTEXT NOT NULL,
    prefix TINYTEXT NOT NULL,
    suffix TINYTEXT NOT NULL,
    nameorder TINYINT NOT NULL,
    famc VARCHAR(22) NOT NULL,
    metaphone VARCHAR(15) NOT NULL,
    living TINYINT NOT NULL,
    private TINYINT NOT NULL,
    branch VARCHAR(512) NOT NULL,
    changedby VARCHAR(100) NOT NULL,
    edituser VARCHAR(100) NOT NULL,
    edittime INT NOT NULL,
    PRIMARY KEY (ID),
    UNIQUE gedpers (gedcom, personID),
    INDEX lastname (lastname, firstname),
    INDEX firstname (firstname),
    INDEX gedlast (gedcom, lastname, firstname),
    INDEX gedfirst (gedcom, firstname),
    INDEX birthplace (birthplace(20)),
    INDEX altbirthplace (altbirthplace(20)),
    INDEX deathplace (deathplace(20)),
    INDEX burialplace (burialplace(20)),
    INDEX baptplace (baptplace(20)),
    INDEX confplace (confplace(20)),
    INDEX initplace (initplace(20)),
    INDEX endlplace (endlplace(20)),
    INDEX changedate (changedate)
)
```

#### HeritagePress Current Structure:

```sql
CREATE TABLE hp_persons (
    id int(11) NOT NULL AUTO_INCREMENT,
    gedcom_id varchar(50) NOT NULL,
    tree_id varchar(50) DEFAULT 'main',
    first_name varchar(255) DEFAULT NULL,
    middle_name varchar(255) DEFAULT NULL,
    last_name varchar(255) DEFAULT NULL,
    -- Different field names, missing many TNG fields
)
```

### 2. FAMILIES TABLE

#### TNG Structure:

```sql
CREATE TABLE families (
    ID INT NOT NULL AUTO_INCREMENT,
    gedcom VARCHAR(20) NOT NULL,
    familyID VARCHAR(22) NOT NULL,
    husband VARCHAR(22) NOT NULL,
    wife VARCHAR(22) NOT NULL,
    marrdate VARCHAR(50) NOT NULL,
    marrdatetr DATE NOT NULL,
    marrplace TEXT NOT NULL,
    marrtype VARCHAR(90) NOT NULL,
    divdate VARCHAR(50) NOT NULL,
    divdatetr DATE NOT NULL,
    divplace TEXT NOT NULL,
    status VARCHAR(20) NOT NULL,
    sealdate VARCHAR(50) NOT NULL,
    sealdatetr DATE NOT NULL,
    sealplace TEXT NOT NULL,
    husborder TINYINT NOT NULL,
    wifeorder TINYINT NOT NULL,
    changedate DATETIME NOT NULL,
    living TINYINT NOT NULL,
    private TINYINT NOT NULL,
    branch VARCHAR(512) NOT NULL,
    changedby VARCHAR(100) NOT NULL,
    edituser VARCHAR(100) NOT NULL,
    edittime INT NOT NULL,
    -- Plus indexes
)
```

## Rebuild Strategy

### Phase 1: Create TNG-Compatible Tables

1. **Backup existing data** (if any)
2. **Drop current tables**
3. **Create new tables with exact TNG structure**
4. **Add WordPress-specific enhancements as separate columns**

### Phase 2: Create Mapping Layer

1. **Data Access Layer** - Abstract class for table operations
2. **TNG Import/Export Layer** - Direct TNG compatibility
3. **WordPress Integration Layer** - WordPress user/permission mapping

### Phase 3: Migration Tools

1. **TNG-to-HeritagePress import scripts**
2. **GEDCOM import with TNG compatibility**
3. **Data validation and integrity tools**

## Implementation Plan

### Step 1: Create TNG-Compatible Core Tables

- Rebuild `hp_people` to match TNG `people` exactly
- Rebuild `hp_families` to match TNG `families` exactly
- Rebuild `hp_events` to match TNG `events` exactly
- Add WordPress-specific fields as additional columns

### Step 2: Build Mapping Classes

- `HP_TNG_Mapper` - Handles TNG data format conversion
- `HP_Data_Access` - Unified data access layer
- `HP_Import_Export` - TNG/GEDCOM import/export tools

### Step 3: Update Database Classes

- Modify database creation classes to use TNG structure
- Add migration scripts for existing data
- Create data validation tools

## Next Actions

1. **Confirm approach** - Full TNG compatibility vs. mapping layer
2. **Backup existing plugin state**
3. **Begin rebuilding core tables** (people, families, events)
4. **Create mapping classes**
5. **Test with actual TNG data**

Would you like to proceed with the full TNG-compatible rebuild?
