# piwigo-openstreetmap

OpenStreetMap integration for the [Piwigo](https://piwigo.org/) photo gallery. Displays geotagged photos on maps.

**Development repository:** [lfflr/piwigo-openstreetmap](https://github.com/lfflr/piwigo-openstreetmap)  
**Original upstream:** [xbgmsharp/piwigo-openstreetmap](https://github.com/xbgmsharp/piwigo-openstreetmap)

This repository is used for further development. The upstream project remains the reference for comparing changes and tracking fixes. No compatibility or security fixes should be assumed completed solely because this repository exists.

## Installation

Place the plugin in `<piwigo-root>/plugins/piwigo-openstreetmap/`. The folder name must be exactly `piwigo-openstreetmap`.

To clone this development repository:

```bash
cd <piwigo-root>/plugins
git clone https://github.com/lfflr/piwigo-openstreetmap.git
```

Or obtain a ZIP of a verified release from this repository and ensure the extracted top-level folder is named `piwigo-openstreetmap`. Activate the plugin in Piwigo's administration panel. **Do not install untested development branches into production.**

## Usage

Geotagged photos can be displayed on photo maps. The plugin also includes album/world maps, GPX functionality, place presets, batch geotagging and reverse-geocoding-based tags (dependent on the Piwigo `tag_groups` plugin).

## Development and documentation

See [Development handover](docs/DEVELOPMENT-HANDOVER.md) for the existing static-code-analysis findings, outstanding security and functional issues, test plan, and proposed development stages.

Development branches and pull requests should target this repository's `master` branch. Compare with [upstream](https://github.com/xbgmsharp/piwigo-openstreetmap) before adopting any changes. The handover is an assessment, **not** evidence of executed tests or corrected vulnerabilities.

Project issues: [lfflr/piwigo-openstreetmap/issues](https://github.com/lfflr/piwigo-openstreetmap/issues).  
Original documentation: [upstream wiki](https://github.com/xbgmsharp/piwigo-openstreetmap/wiki).  
Original Piwigo extension page: [extension 701](https://piwigo.org/ext/extension_view.php?eid=701).

## Credits and license

Originally developed in [xbgmsharp/piwigo-openstreetmap](https://github.com/xbgmsharp/piwigo-openstreetmap). Uses [OpenStreetMap](https://www.openstreetmap.org/), [Leaflet](https://leafletjs.com/) and [Leaflet.markercluster](https://github.com/Leaflet/Leaflet.markercluster).

The Piwigo OpenStreetMap plugin is free software under the GNU General Public License. See the repository's licensing files and original notices for applicable terms; no change to the project's license is intended by this documentation update.
