# Customization Guide

This guide describes safe methods for making changes to the software without editing core files. Following these practices ensures that your customizations are preserved during future updates and will not cause conflicts.

There are two primary approaches to customization:

1.  **File Overrides:** Ideal for major changes to a file's structure or logic.
2.  **Using Hooks:** The best choice for small, targeted modifications to data or behavior.

-----

## Method 1: File Overrides via the `/custom/` Directory

This method allows you to completely replace a core file with your own version. The system will automatically load the file from the `/custom/` directory, ignoring the default path.

#### When to Use This Method:

  * When completely rewriting the logic of a PHP controller or class.
  * When making significant changes to the HTML structure of a Smarty (`.tpl`) template.
  * When replacing a default CSS or JS file with your own heavily modified version.

#### How to Override a File:

1.  Identify the path to the core file you want to modify.
2.  Recreate the exact same directory structure inside the `/custom/` folder.
3.  Copy the original file to the newly created directory and apply your changes there.

**Example: Customizing a Listing Type Page Controller**

  * **Original File:** `/includes/controllers/listing_type.inc.php`
  * **Your Override File:** `/custom/includes/controllers/listing_type.inc.php`

> **Key Advantage:** This approach completely isolates your changes. When you update the software, you can safely apply official patches without worrying about merge conflicts in your customized files.

**This method is applicable for the following file types:**

  * `.php` (classes and controllers)
  * `.tpl` (Smarty template files)
  * `.css` (stylesheet files)
  * `.js` (script files)

-----

## Method 2: Using Hooks for Granular Changes

For smaller modifications where overriding an entire file is excessive, use **hooks**. Hooks are specific "points" in the code that allow you to connect to the standard logic to add or alter data.

All of your hook logic should be located in a single class:

  * **Location:** `/includes/classes/rlCustom.class.php`

### How to Work with Hooks

The process consists of two main stages: **implementation** and **activation**.

#### Step 1: Find and Implement the Hook

1.  **Find the Hook:** Locate the desired hook point in the core files (`.php` or `.tpl`). They look like `$rlHook->load('hookName', ...)` in PHP or `{rlHook name='hookName'}` in templates.
2.  **Implement the Method:** Create a public method in the `rlCustom` class. The method name can be anything, but the best option is to follow the format: `hook` + `HookName` (in PascalCase).

#### Step 2: Activate the Hook

**Dynamic Activation in the Constructor**

You can run the simple code via an anonymous function or a separate method within the class.

```php
// /includes/classes/rlCustom.class.php

class rlCustom extends reefless
{
    public function __construct()
    {
        // Option 1: Anonymous Function (for simple actions)
        $GLOBALS['rlHook']->addCustomHook('tplHeader', function() {
            echo '<link rel="stylesheet" href="/custom/my_styles.css" type="text/css" />';
        });

        // Option 2: Calling a method from this class (for complex logic)
        $GLOBALS['rlHook']->addCustomHook('listingsModifyWhere', [$this, 'hookListingsModifyWhere']);
    }

    // Method for the `listingsModifyWhere` hook
    public function hookListingsModifyWhere(&$sql)
    {
        // Add a condition to select only "Featured" listings
        // The `&` before `$sql` means the variable is passed by reference and will be modified in the core.
        $sql .= " AND `T1`.`Featured_ID` > 0 ";
    }
}
```

-----

### Examples from the Documentation

#### PHP Hook: Modify an SQL Query

  * **Goal:** Display only "Featured" listings in a list.
  * **Hook:** `listingsModifyWhere` in the file `/includes/classes/rlListings.class.php`.
  * **Implementation:**
    ```php
    // In rlCustom.class.php
    public function hookListingsModifyWhere(&$sql)
    {
        $sql .= " AND `T1`.`Featured_ID` > 0 ";
    }
    ```
  * **Activation:** Use the dynamic method in the constructor as shown above.

#### TPL Hook: Add CSS to the `<head>`

  * **Goal:** Add custom styles to the website.
  * **Hook:** `tplHeader` in the file `/templates/{your_theme}/tpl/head.tpl`.
  * **Implementation:**
    ```php
    // In rlCustom.class.php
    public function hookTplHeader()
    {
        echo "<style>body { background-color: #f0f2f5; }</style>";
    }
    ```
  * **Activation:** Use the dynamic method in the constructor as shown above.

> **Pro-Tip:** Explore the `/includes/classes/rlCustom.class.php` file in the distribution package to find more examples and ready-made solutions.
