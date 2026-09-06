[🇫🇷 Version française](user_guide.fr.md) | 🇬🇧 English version

---

# User guide

This guide covers using Summit Stats to import GPX traces from trail runs
and hikes, and to track your progression over time.

## Logging in

Summit Stats is a single-user (or small-group) tool: authenticate with the
password configured for your account to get a session token. If you're
redirected to `/login`, your token has expired or isn't set yet.

## Importing an activity

1. From the dashboard, drag and drop (or select) a GPX file. Files exported
   from Komoot, or any GPX source, are supported, including C:Geo traces
   that may be missing elevation data.
2. If the file lacks altitude data, Summit Stats automatically enriches it
   via the OpenTopoData API before analysis; upload progress is shown in
   real time.
3. Once uploaded, the activity is analyzed automatically: the track is
   segmented by terrain type (ascent / flat / descent) and by slope class
   (5 categories, from flat to extreme), and 22 stats are computed and
   stored.
4. Fill in or edit the activity's metadata (title, type, environment) from
   its detail page at any time.

## Understanding your stats

Each activity stores 22 metrics, including:

- Total and moving speed (pauses longer than 30 seconds are excluded from
  moving time)
- Ascent speed: average, to the summit, and over the longest non-descending
  segment
- Descent rate
- The percentage breakdown of both ascent and descent by slope class

If you need to recompute an activity's stats (after a config change, for
example), use the recalculate button on its detail page, or run
`php artisan stats:recalculate` to recompute every stored activity at once.

## Exploring an activity

The activity detail page shows the elevation profile and an interactive OSM
map of the track side by side. Hovering over a point on the elevation
profile highlights the corresponding point on the map, and vice versa, so
you can pinpoint exactly where a given slope or pace occurred on the trail.

## Tracking progression

The dashboard's progression charts let you compose your own view of your
data:

- Pick the **metric** you want to track (e.g. average ascent speed).
- Filter by **activity type**, **environment**, **period**, and **slope
  range** (from/to), combining as many filters as you need.
- Charts recompute on the fly as filters change, so you can quickly answer
  questions like "am I faster in trail running than hiking on moderate
  gradients?" without exporting data elsewhere.

## Activity history

The activity list is paginated and filterable the same way as the
progression dashboard, with summary stats shown per activity so you can
scan your history at a glance before opening a specific one.
