# dooplay-redirect-customizer---Plugin
Replace DooPlay link redirect page with a responsive redirect UI and admin settings page (tabs) for timer, colors and backgrounds.

# DooPlay Redirect Customizer

**Contributors:** code-copilot  
**Tags:** dooplay, redirect, customizer, netflix-style, links  
**Requires at least:** 5.0  
**Tested up to:** 6.7  
**Requires PHP:** 7.4  
**License:** GPLv2 or later  

## Description

A complete redirect system replacing DooPlay’s `/links/` pages with a responsive, modern UI.

### Key Features
* **Netflix-style UI:** A dark, cinematic redirect page with countdown timers.
* **Server-side Redirects:** Secure `/go/{token}/` handling.
* **AdBlock Detection:** Pauses the timer if AdBlock is detected (non-intrusive).
* **Role-Based Logic:** * *Admins:* No timer, no ads.
    * *Contributors:* 5s timer, no ads.
    * *Subscribers:* Half timer, reduced ads.
    * *Guests:* Full timer, full ads.
* **Background System:** Supports Images, GIFs, and Video backgrounds.
* **Click Counter:** Updates DooPlay's `dt_views_count` automatically.
* **Shortener Integration:** API manager with parser rules for link shorteners.
* **Custom Animations:** Lottie JSON support for loaders.

## Installation

1.  Download the repository as a ZIP file.
2.  Upload the plugin to your WordPress site under **Plugins > Add New > Upload Plugin**.
3.  Activate **DooPlay Redirect Customizer**.
4.  Go to **DP Redirects** in your admin dashboard to configure settings.

## Configuration

### General Settings
* **Timer:** Set the countdown duration (default: 5 seconds).
* **Accent Color:** Choose the primary color for progress bars and buttons (default: Netflix Red `#e50914`).
* **Behavior:** Choose between "Auto Redirect" or "Show Continue Button".

### Monetization
* **Role Logic:** Enable to give logged-in users faster redirects.
* **Ad Spots:** Paste your HTML/JS code for Top (728x90) and Bottom (300x250) ad slots.

## Screenshots

*(You can upload screenshots to an 'assets' folder in your repo and link them here)*

## Changelog

### 1.2.1
* Initial public release.
* Added role-based logic and ad-block detection.
* Merged desktop and mobile CSS styles.
