# Epidemiology dashboard data contract

The home screen is an epidemiology-first operational dashboard. The GitHub Pages build uses synthetic sample data; it does not connect to live RHU records. The PHP backend does not yet expose an epidemiology dashboard endpoint.

## Data adapter

Before the dashboard's JavaScript initializes, a future PHP-rendered page or authenticated API integration may assign an aggregate object to `window.CHITS_EPI_DATA`. The UI recognizes it only when both `points` (array) and `summary` (object) are present. Set `source: "backend"` and include an `asOf` timestamp to distinguish sourced data from the demo.

Example shape:

```js
window.CHITS_EPI_DATA = {
  source: "backend",
  asOf: "2026-10-22T08:00:00+08:00",
  summary: {
    newCases: 31,
    priorityAlerts: 3,
    barangaysWithSignals: 4,
    barangaysTotal: 9,
    completenessPercent: 89
  },
  points: [
    {
      barangay: "Barangay example",
      lat: 8.95,
      lng: 125.54,
      level: "watch",
      cases: { Dengue: 4, Diarrhea: 2, ARI: 3, TB: 1 }
    }
  ],
  alerts: [
    {
      condition: "Dengue",
      barangay: "Barangay example",
      count: 4,
      level: "high",
      note: "Review line list and verify the signal"
    }
  ],
  trends: {
    labels: ["W38", "W39", "W40", "W41"],
    datasets: [
      { label: "Dengue", data: [2, 3, 2, 4] }
    ]
  }
};
```

The values above are illustrative schema examples, not real epidemiological findings. The dashboard does not currently calculate alerts, incidence rates, expected thresholds, or reporting completeness. Those must be computed and validated by the backend using approved definitions and epidemiologic weeks.

## Requirements before operational use

- Replace sample coordinates with verified barangay centroids or official barangay boundary polygons/GeoJSON. A point map is not a choropleth and must not be presented as an exact administrative boundary map.
- Provide an explicit reporting period, epidemiologic week/year, timezone, refresh timestamp, source and data-quality status.
- Use approved case definitions, duplicate resolution, date-of-onset rules, alert thresholds and authorized investigation workflows. Distinguish suspected, probable and confirmed cases as appropriate for the condition.
- Calculate rates only with a valid population denominator and defined period. Avoid comparing raw counts across barangays with very different populations.
- Represent reporting completeness using expected versus received reports, including explicit zero reports. Missing reports must not be treated as zero cases.
- Show aggregate data only. Do not put patient names, exact household coordinates, contact details, line lists or other identifiable information in map payloads or public GitHub Pages assets. Apply role-based access, audit trails, retention controls and privacy review in the authenticated application.
- Add a backend-backed alert review workflow with assigned owner, verification status, action taken, escalation and audit timestamps. A statistical signal is a prompt for verification, not by itself a confirmed outbreak.
- Review the OpenStreetMap tile usage policy and select a suitable tile provider before production use. The current public demo uses OpenStreetMap tiles for low-volume demonstration only.

## Map and tile attribution

The map is built with Leaflet and OpenStreetMap tiles. Leaflet's documentation recommends reading the tile provider's terms; OpenStreetMap requires visible attribution and does not guarantee an SLA for its public tile service:
- https://leafletjs.com/examples/quick-start/
- https://operations.osmfoundation.org/policies/tiles/

## Current limitations

The static demo displays synthetic aggregate values and approximate illustrative points. The local PHP pilot currently handles patient registration and consultations, not the epidemiology aggregation contract above. The dashboard is prepared to accept a backend-supplied aggregate object, but a real data pipeline, endpoint, authorization checks and validation are still required before this screen can be described as live surveillance.
