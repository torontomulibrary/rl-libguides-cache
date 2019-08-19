# rl-libguides-cache
Periodically caches data from the LibGuides API to be used inside WordPress.

## Installation
1. Use the [GitHub Updater](https://github.com/afragen/github-updater) plugin to install. Or extract manually into the `wp-content/wp-plugins` directory.
2. Set up API Credentials in the WordPress dashboard under `Settings > LibGuides Cache`.
3. Refresh the cache under `Settings > LibGuides Cache > Refresh Cache`.

## Usage
### `[rl_subject_librarians]` shortcode
Outputs a table of subjects and profiles associated with that subject.
