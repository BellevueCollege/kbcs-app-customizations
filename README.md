# KBCS Custom Feeds Plugin

This plugin provides a custom Wordpress feed utilizing the Playlist Center API. The custom feeds integrate show/episode information from the Playlist Center with program information from the KBCS Wordpress website.

The plugin adds an additional feed endpoint of `shows` which can currently be used in two scenarios.

## Program feed

- **Template URL:** http://kbcs.fm/programs/[program name]/shows
- **Example usage:** http://kbcs.fm/programs/democracy-now/shows
- **What it does:** It gets a feed of the last X shows for the provided program.

## Program type aggregate feed

- **Template URL:** http://kbcs.fm/program_type/[program type]/shows
- **Example usage:** http://kbcs.fm/program_type/music/shows
- **What it does:** It provides an aggregate feed of the last X shows for all programs (combined) of the given program type.

>Note: Be wary of usage. This is an intensive feed to generate as there is currently not a way in the Playlist Center API to pull program show information in aggregate. This feed does its best version of it by getting the last 15 shows for each program, aggregating all those items, sorting, then slicing off the number requested.

## Customizable options

### Item count
An additional query parameter of `itemCount` and value can be added to the URL to alter the number of returned items in the feed.

- **Example:** http://kbcs.fm/programs/democracy-now/shows?itemCount=30

If this value is not provided, the feed will return the default number of items as set in the Wordpress site options.


