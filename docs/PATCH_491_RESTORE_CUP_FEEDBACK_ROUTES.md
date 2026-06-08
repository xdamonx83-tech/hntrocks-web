# Patch 491 — Restore Cup Feedback Routes after Chat Tabs

Restores the cup feedback frontend/admin routes that were accidentally lost when the chat-tabs patch shipped a routes/web.php without the Patch 487 cup-feedback routes.

Changed:
- routes/web.php
  - Restored CupFeedbackController import
  - Restored AdminCupFeedbackController import
  - Restored /cup-feedback GET/POST routes
  - Restored /admin/cup-feedback GET/POST routes
  - Kept the chat-tab route from Patch 489

No migration required.
