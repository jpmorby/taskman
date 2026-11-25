# Taskman

![GitHub issues](https://img.shields.io/github/issues/jpmorby/taskman) ![GitHub pull requests](https://img.shields.io/github/issues-pr/jpmorby/taskman) ![GitHub](https://img.shields.io/github/license/jpmorby/taskman) ![GitHub last commit](https://img.shields.io/github/last-commit/jpmorby/taskman) ![Laravel Version](https://img.shields.io/badge/Laravel-11.x-green) ![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue) ![PEST Tests](https://github.com/jpmorby/taskman/actions/workflows/tests.yml/badge.svg)
![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/jpmorby/taskman/tests.yml?branch=main)  ![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/jpmorby/taskman)

## Introduction

Rudimentary first pass at a todo list in livewire 3.6, Tailwind v4 and FluxUI .. mainly as an exercise to try and do CRUD things with livewire / writing an SPA / etc

### FluxUI 2.x

Using as many FluxUI features as I can to simplify design (as I'm lousy at design)

#### Features

- Per user task lists
- WorkOS authentication / AuthKit so support for SSO via Google, GitHub and Apple, Passkey and more
- Sortable and filterable
- Keyboard shortcuts (Cmd-A add, Cmd-K search)
- Multiple languages (locale) supported with translations for Portugese, Spanish, French, Italian, German and Russian so far (thanks to ChatGPT - if someone wants to tidy them up, please feel free!)
- Export and import your tasks to a json format
- Mobile friendly interface

##### TO DO

- Categories
- ~~Backups / Export functionality (JSON?)~~
- ~~Translations~~
- ~~Responsive / mobile friendly design~~
- Support for Markdown .. probably using flux:editor
- Add Sentry and put a version live on task.me.uk ???
- Reverb for notifications (backup jobs / etc)
- Add file attachments so you can add docs to the tasks

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## Deployment

Taskman deploys automatically. It is hosted on [FXRM](https://fxrm.com) web hosting, and the site is connected to this GitHub repository through Enhance's **Deploy from GitHub** feature with auto-deploy turned on for the `main` branch. Every push to `main` starts a new deploy. Do not deploy it by hand.

**The Envoy deploy is retired.** Do not run `envoy run deploy`. `Envoy.blade.php` is kept for reference only.

Why: on 17 November 2026 a dependency update was deployed with `envoy run deploy` while the auto-deploy was already running for the same push. The two deploys ran at the same time and the deploy broke. Push to `main`, check the deploy result, and never start a second deploy alongside it.

## Hosting your own copy on FXRM

Want push-to-deploy for your own Laravel app? Taskman runs on [FXRM web hosting](https://fxrm.com/hosting/web-hosting), and you can set up the same thing:

1. Connect GitHub in the Enhance control panel. Public or private repositories both work, and you choose which repositories the GitHub app can see.
2. Pick the repository and the branch or tag to deploy from.
3. Deploy by hand when you're ready, or turn on auto-deploy so every push to that branch or tag starts a new deploy.

FXRM web hosting runs PHP, Laravel, Node.js, static sites and custom apps in UK data centres.

**[Get FXRM web hosting](https://fxrm.com/hosting/web-hosting)**
