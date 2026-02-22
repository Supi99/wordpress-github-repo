# GitHub Repos

A WordPress plugin to display your public GitHub repositories as cards using the GitHub API.

![GitHub Repos screenshot](screenshot.png)

## Features

- Displays all your public repositories (forks excluded) as a responsive card grid
- Each card shows the repo name, description, and a link to GitHub
- Results cached for 10 minutes to stay within GitHub API rate limits
- Optional Personal Access Token support to raise the rate limit from 60 to 5000 requests/hour
- Pure vanilla JS — no dependencies

## Requirements

- WordPress 5.8+
- PHP 7.4+
- A public GitHub account

## Installation

1. Download or clone this repository into your `wp-content/plugins/` directory:
   ```
   git clone https://github.com/YOUR_USERNAME/github-repos wp-content/plugins/github-repos
   ```
2. Activate the plugin from the WordPress admin panel under **Plugins**.
3. Go to **Settings → GitHub Repos** and enter your GitHub username.
4. Add the shortcode `[github_repos]` to any page or post.

## Configuration

| Option | Required | Description |
|---|---|---|
| GitHub Username | Yes | Your GitHub username |
| Personal Access Token | No | Increases API rate limit from 60 to 5000 req/hour. Create one at [github.com/settings/tokens](https://github.com/settings/tokens) with `public_repo` scope |

## Usage

Place the shortcode anywhere on your site:

```
[github_repos]
```

## File structure

```
github-repos/
├── github-repos.php   # Plugin backend (WordPress + GitHub API)
├── github-repos.js    # Frontend UI
├── github-repos.css   # Styles
└── README.md
```

## License

MIT
