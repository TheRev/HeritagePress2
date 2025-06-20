Objective:
Port functionality from TNG to the HeritagePress WordPress plugin. Replicate core behavior and structure while modernizing implementation for WordPress standards.

🔧 Instructions
Analyze the file:

{FILENAME}

Identify and include all related/dependent files (includes, scripts, handlers) in your analysis.

Before coding:
Tell me what this files does.

Provide a list of all related files of the file we are analyzing.

Replicate functionality in HeritagePress plugin only if not already implemented:

Do not create mock code or test scaffolding; work directly in the live plugin.

If the functionality or related functions already exist in HeritagePress:

Ensure your additions integrate cleanly and work correctly with existing code.

Refactor or rewrite the existing logic as needed to maintain compatibility and proper functionality with the new additions that make the function better.

If the file corresponds to an admin page:

Add it to the WordPress admin menu under the correct section.

Replicate all form fields, tab/section layouts, field groupings, and options exactly as in the original TNG admin page. Nothing should be missing.

Update backend logic to follow WordPress best practices (e.g., $wpdb, options API, CPTs, or custom tables).

🧩 Development Guidelines
Preserve UI/UX:
Retain original layout, format, and option placement on admin pages for familiarity.

Modernize backend logic:
Focus on replicating front-end/admin behavior, not legacy code structure. Replace deprecated or obsolete patterns such as:

Raw SQL queries → use $wpdb

Inline procedural templates → use admin page hooks and template structure

Global variables or insecure patterns

Naming and structure:

No “TNG” in new files, functions, classes, or identifiers.

Prefix custom functions/classes with heritagepress\_.

Place files in proper plugin directories (/admin, /includes, /assets).

Follow WordPress coding/file structure standards.

Security and stability:

Sanitize and validate all inputs.

Use WordPress nonces for form security.

Avoid PHP warnings/errors or broken admin views.

Architecture:

Separate logic from display where possible.

Use WordPress hooks (actions/filters) for extensibility.

📦 Deliverable
Fully functional, production-ready WordPress admin page/module inside HeritagePress.

Exact UI layout and functionality replication from TNG admin page.

Updated, modern backend logic.

Secure, commented, clean code following WP standards.

All required features working as expected.

To-do or follow-up items

Reminder for AI:
Before starting any file, check HeritagePrice files to avoid duplication. If already done, notify and skip. If not, proceed and update the file after completion. Do not create any files placing tng or TNG in the file name, file code, or mention tng or TNG within the files.

If any of the files have administration menus and they are not part of the main menus (Trees, People, Families, Media, Sources, Repositories, and Import / Export ) then all other admin pages will be placed on the HeritagePress Dashboard in the appropriate areas. Do not rearrange or remove the style of the dashboard.

When you have created the above tasks then go through the files we just created or were working with and compare them against their matching tng files to make sure you did not miss any functions or connections to database fields. Do not cheat this. Do it.
