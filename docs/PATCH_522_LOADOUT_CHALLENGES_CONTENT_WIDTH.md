# Patch 522: Loadout-Challenges Content Width

Fixes the public Loadout-Challenges pages using the already compiled Socialite container classes instead of the non-existing `max-w-[1120px]` utility.

Changed files:
- resources/views/themes/socialite/loadout-challenges/index.blade.php
- resources/views/themes/socialite/loadout-challenges/show.blade.php

No route, migration, controller, model, CSS or JavaScript changes.
