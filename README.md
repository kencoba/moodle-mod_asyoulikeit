[![Moodle Plugin CI](https://github.com/kencoba/moodle-mod_asyoulikeit/actions/workflows/moodle-ci.yml/badge.svg)](https://github.com/kencoba/moodle-mod_asyoulikeit/actions/workflows/moodle-ci.yml)

# mod_asyoulikeit

A Moodle activity module where students enter an assignment, submit their work (optionally
with file attachments), and give a limited number of "likes" to their peers' submissions.

## Features

- **Enter** an assignment to get a budget of 5 likes to spend within it.
- **Submit** work: a title, content, an optional comment, optional file attachments, and a
  public/private visibility.
- **Edit** or **delete** your own submission at any time. Deleting a submission that has likes
  on it revokes those likes and returns them to each giver's budget.
- **Like** / **revoke** a like on someone else's submission, as long as you have likes
  remaining and haven't already liked that submission.
- Only a submission's own author can change its **visibility**.
- A **reviewer report** (`mod/asyoulikeit:viewallsubmissions`, granted to teachers/managers by
  default) lists every participant, their submission status, visibility, last-modified time,
  and like count — including private submissions, so a teacher can actually review the
  activity rather than only seeing what students chose to publish.

## Project layout

```
classes/local/domain/    Moodle-free domain layer (entities, application services, ports)
classes/local/infra/     $DB-backed repository implementations
classes/local/form/      Moodle forms
classes/event/           Moodle events
tests/                   Moodle-bootstrapped tests (advanced_testcase) + test data generator
tests-fast/              Standalone PHPUnit suite for classes/local/domain, no Moodle needed
templates/               Mustache templates used by renderer.php
```

## Running the fast domain tests

These don't need a Moodle install — only PHP and Composer:

```bash
cd tests-fast
composer install
vendor/bin/phpunit
```

## Development environment

Local development and manual testing use
[moodle-docker](https://github.com/moodlehq/moodle-docker) against Moodle 4.5 LTS
(`MOODLE_405_STABLE`). See that project's README for the general setup; this plugin is
checked out at `mod/asyoulikeit` inside the Moodle codebase the containers mount.

## Known limitations

- **No backup/restore support** (`backup/moodle2/`). This is required before the plugin could
  be submitted to the official Moodle Plugins directory; it's tracked as follow-up work.
- Targets Moodle 4.5 LTS only; the `public/` directory restructure introduced in Moodle 5.1+
  is not accounted for.
- No grading integration (`FEATURE_GRADE_HAS_GRADE` is `false`) — likes are peer feedback, not
  a grade.
- Multiple submissions per participant per assignment are allowed by design (not restricted to
  one).

## License

GPL-3.0-or-later, as required for Moodle plugins. See [LICENSE](LICENSE).
