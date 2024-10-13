#### 4.2.0: 21.08.2024
Added: DLM PRO install feature

#### 4.1.11 30.07.2024
* Added: Import download slug

#### 4.1.10 01.02.2024
* Fixed: Importing published date issue

#### 4.1.9 27.07.2023
* Fixed: Add download_count meta to versions

#### 4.1.8 03.07.2023
* Changed: Notice informations
* Changed: Stop functionality if license is not valid

#### 4.1.7 24.05.2023
* Changed: Setting default version names to imported versions without name.
* Added: Thumbnail( featured image ) import.
* Added: Activation/Deactivation hooks for license
* Changed: Populate published date from CSV column

#### 4.1.6 09.08.2022
* Changed: Notices priority set to 8.
* Fixed: Fix for PHP 5.6 error "unexpected '::' (T_PAAMAYIM_NEKUDOTAYIM)".

#### 4.1.5 28.03.2022
*Fixed: Minor importing error with file_urls

#### 4.1.4 28.02.2022
* Changed: Dismissable license notice & show notice only on dlm pages
* Fixed: The importer doesn't create the version for the downloads.

#### 4.1.3 22.12.2021
* Added: Plugin inline notifications
* Fixed: Changed offset syntax from curly to square braces ( https://github.com/WPChill/dlm-csv-importer/issues/6 )

#### 4.1.2: 29.11.2021
* Changed: License and update checking system
* Changed: Extensions markup
* Changed: Notice display
* Added CSV file type to supported mimes

#### 4.1.1: November 27, 2018
* Tweak: Fixed an issue where old versions still existed when overriding existing downloads.

#### 4.1.0: June 25, 2018
* Feature: It's now possible to add multiple meta data values with the same key. By default, these will be split on comma (,).

#### 4.0.0: January 20, 2018
* Tweak: Made plugin compatible with Download Monitor 4.0
* Tweak: Replaced custom autoloader with Composer class map.

### 1.4.1: May 10, 2017
* Tweak: Fixed a timezone bug that caused imported downloads to be scheduled instead of published.
* Tweak: Check if data is set per row for given headers, preventing notices.
* Tweak: Updated extension register method for better update support.

### 1.4.0: May 27, 2016
* Feature: Downloads can now be overridden/updated based on download ID.
* Tweak: Downloads can no longer be overridden/updated based on download title.

### 1.3.0: May 3, 2016
* Feature: Downloads can now be overridden/updated if the CSV title equals an existing download title.
* Tweak: Properly clearing transients after importing now, fixes bug where downloads needed a re-save after importing.

### 1.2.4: March 4, 2016
* Tweak: Don't parse files on import because they're parsed on download.
* Tweak: Set total version download as download count on import.

### 1.2.3: July 1, 2015
* Tweak: Added the ability to have download and version in 1 row if the file only has a URL.

### 1.2.2: June 23, 2015
* Tweak: Fixed asset enqueue bug.

### 1.2.1: June 22, 2015
* Tweak: Added checks to non mandatory version data, fixes notices.
* Tweak: Now displaying example version data in mapping screen as well.

### 1.2.0: June 10, 2015
* Feature: Added possibility to add custom meta fields to downloads.

### 1.1.0: February 22, 2015
* Feature: Added possibility to import (multiple) versions with all the version meta. Note to existing user: CSV format changed, see: https://www.download-monitor.com/documentation/csv-importer/
* Tweak: Now displaying the amount of rows found at mapping screen.

### 1.0.1: February 6, 2015
* Tweak: Improved mandatory title column check
* Tweak: Fixed missing csv column data checks

### 1.0.0: February 4, 2015
* Initial Release