Thanks for your interest in LAN Ahead!

Bugs, feature requests, and general development needs are tracked in [GitHub issues](https://github.com/misterhaan/lana/issues).  Everyone is welcome to create issues or comment on existing issues, even if you don’t know anything about programming!

If you want to contribute code to address an issue you can submit a pull request and I’ll review it when I have time.  Contributed code is considered a gift to the project and does not grant any ownership of the project or the website.  Do not submit code you didn’t write (AI assistance such as GitHub Copilot is acceptable).  Significant contributions might be recognized on the website.

Project code should conform to standards to make it easier to work with.  Some are called out here but in general if there’s an established pattern in the repository do your best to follow it.

## Formatting
- Indent using tabs.  Set you editor to whichever tab width you like to see.
- I edit in VS Code with extensions for the file types in the project.  These provide automatic formatting on save which I go along with even though I don’t agree with everything they do.
- Braces and parentheses should be avoided when not needed.  Programmers are expected to know order of operations and automatic formatting makes sure indentation will tell us which lines are part of a block.
- For text, use two spaces after a period or colon.  If VS Code automatic formatting condenses it to one space, don’t fight it.

## Database
- Tables should be named as a noun in singular form.
	- Usually this noun describes the meaning of a row from that table (such as `game`).
	- Sometimes the noun will describe the entirety of the table or a subset of rows in that table (such as `library`).
	- For tables that mainly exist to link other tables together, the noun can describe the relationship that brings the tables together (such as `friend`).
- Only common abbreviations should be used, such as “min” for “minimum” or “config” for “configuration.”  No one should need to guess what something like “bg” means.
- Column and table names use camelCase — capitalize the first letter of each word except the first word.
	- Names should be one word when possible.
	- Column names assume the context of the table they’re defined in, so many tables can have `id` and `name` columns.
	- “Words” that are actually initials normally written in all caps (such as `URL`) should be all caps if they occur at the end of a multi-word name, but only the first letter capitalized if they occur at the beginning or in the middle.  If they’re the only word, the entire thing should be lowercase.
- Each table / view / procedure / function should be in its own SQL file in the appropriate subdirectory of `/etc/db/`.
	- Filename should be the table / view / procedure / function name (same capitalization) with lowercase `.sql` extension.
	- These files are used to create the database by `/api/setup.php`.
	- After initial release, new and changed database files need to be handled for upgrades in the setup script, including updating the current structure version value.  Write transition SQL files if possible or PHP functions to make the changes as an upgrade.
- Optional columns should use NULL when they do not have a value.
- Every table must have a primary key.
	- Prefer unique data (even if it consists of multiple columns) to an auto_increment column.
	- Single-column primary keys should usually be named `id` so joins and foreign keys know what to link to.
- Foreign key columns should normally be named the same as the table they link to.
- Foreign key columns should cascade updates and either set null or cascade deletes.
- Tables and columns should provide just enough detail in comments to understand meaning.

## PHP
- PHP is exclusively accessed through `/api/` web server calls.
	- Prefer returning data objects to fully-formed HTML.
	- Should return an error HTTP response code if unable to complete request.  Scenarios such as no search results are not an error.
- Use `require_once` when code from another PHP file is needed.
	- Often, this call is immediately before the first line of a function that needs that file.
	- If another PHP file will always or almost always be needed, bring it in at the beginning of the file.
	- Files that import other files should define `CLASS_PATH` to the `etc/class/` path which is then referenced in `require_once` statements.
- API files (in `/api/`) should use classes defined in `/etc/class/` for any logic other than parsing API inputs and checking endpoint-level access.

## JavaScript
- Use modules.  If there’s a main object or class defined in a file, make it the default module export.
- Use Vue where possible.
- Vue components should usually be their own file for easier reusability.
- Filenames should be the camelCase form of the default export, with the lowercase `.js` extension.

## Sass / SCSS / CSS
- Everything gets included in `lana.scss` so that one CSS file is generated.
- `_!base.scss` defines values referenced by other SCSS files.
- General styles are defined in files that begin with `_!`, including components that are part of the general layout.
- Styles specific to a page-level component are in SCSS files named after the component with a `_` prefix.
- Class and ID names made up of multiple words should use kebab-case (all lowercase; words separated by dashes).
- Sass variable names should be camelCase.
- Sass mixin names should be kebab-case.
