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
