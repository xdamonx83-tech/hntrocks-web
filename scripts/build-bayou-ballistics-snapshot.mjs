// Curate factual fields from Bayou Index's publicly served, versioned JS.
// Usage: node scripts/build-bayou-ballistics-snapshot.mjs public-data.mjs public-model.js output.json
// No descriptions, images, game files, or private endpoints are read or copied.
import { createHash } from "node:crypto";
import { readFileSync, writeFileSync } from "node:fs";
import { pathToFileURL } from "node:url";

const [dataPath, modelPath, outputPath] = process.argv.slice(2);
if (!dataPath || !modelPath || !outputPath) throw new Error("Expected public data JS, model JS, and output JSON paths.");
const sha256 = (path) => createHash("sha256").update(readFileSync(path)).digest("hex");
const data = (await import(pathToFileURL(dataPath).href)).n;
if (!Array.isArray(data.weapons) || !data.buildId || !data.hunterZones) throw new Error("Unknown public data shape.");

const ammoPattern = /_(compact|medium|long|shell|special)_([a-z0-9_]+)$/;
const bulletSources = new Set(["CompactBullet", "MediumBullet", "LongBullet"]);
const zoneSources = {
  upperTorsoMultiplier: "torso_upper",
  torsoMultiplier: "torso",
  armMultiplier: "arm_left",
  legMultiplier: "leg_left",
};
const finite = (value) => (typeof value === "number" && Number.isFinite(value) ? value : null);
const effectiveMode = (weapon, ammo, secondary) => {
  const alt = secondary ? weapon.alternate ?? {} : {};
  const override = ammo.ballistics ?? {};
  return {
    perHitDamage: override.perHitDamage ?? alt.perHitDamage ?? weapon.perHitDamage,
    pellets: override.pellets ?? alt.pellets ?? weapon.pellets,
    velocity: override.muzzleVelocity ?? alt.muzzleVelocity ?? weapon.muzzleVelocity,
    loaded: override.ammoLoaded ?? alt.ammoLoaded ?? weapon.ammoLoaded,
    reserve: override.ammoReserve ?? alt.ammoReserve ?? weapon.ammoReserve,
    zeroRange: override.zeroRange ?? alt.zeroRange ?? weapon.zeroRange,
    gravity: override.gravity ?? alt.gravity ?? weapon.gravity,
    projectile: ammo.projectile ?? weapon.projectile,
    damageSource: ammo.damageSource ?? weapon.damageSource,
    falloff: ammo.falloffCurve ?? weapon.falloffCurve,
  };
};

const visible = data.weapons.filter((weapon) => !weapon.hiddenBy?.length);
const weapons = visible.map((weapon) => {
  const modes = [
    ...(weapon.ammoOverrides ?? []).map((ammo) => [ammo, false]),
    ...(weapon.ammoOverrides2 ?? []).map((ammo) => [ammo, true]),
  ].map(([ammo, secondary]) => {
    const values = effectiveMode(weapon, ammo, secondary);
    const parsed = ammo.name?.match(ammoPattern);
    const projectile = values.projectile ?? {};
    const standardBullet = bulletSources.has(values.damageSource) && (values.pellets ?? 1) === 1 &&
      !ammo.projectileAnomaly && !ammo.impactBurst;
    const stats = {
      baseDamage: standardBullet ? finite(values.perHitDamage) : null,
      ...Object.fromEntries(Object.entries(zoneSources).map(([field, zone]) => [
        field, standardBullet ? finite(data.hunterZones[zone]?.multipliers?.[values.damageSource]) : null,
      ])),
    };
    // Head is an instant-kill presentation on the public page, not an approved
    // numeric HNT damage multiplier. Never emit a headMultiplier CREATE value.
    const flight = standardBullet && values.velocity > 0 && values.gravity < 0 &&
      values.zeroRange >= 0 && (projectile.gravity ?? 0) === 0 &&
      (projectile.airResistance ?? 0) === 0 ? {
        model: "bayou_public_zero_range_gravity_v1",
        zero_range_m: values.zeroRange,
        gravity_mps2: values.gravity,
        pre_gravity_mps2: 0,
        air_resistance_per_s: 0,
        muzzle_velocity_mps: values.velocity,
      } : null;
    return {
      id: ammo.id,
      name: ammo.name,
      ammo_class: parsed?.[1] ?? null,
      ammo_name: parsed?.[2] ?? null,
      barrel: secondary ? "secondary" : "primary",
      damage_source: values.damageSource,
      projectile_kind: standardBullet ? "standard_bullet" : "unsupported",
      stats,
      checks: {
        velocity: finite(values.velocity), loaded: finite(values.loaded), reserve: finite(values.reserve),
        damage: finite(weapon.cardStats?.Damage + (ammo.stats?.Damage ?? 0)),
        dropRange: finite(weapon.cardStats?.["Drop Range"] + (ammo.stats?.["Drop Range"] ?? 0)),
      },
      falloff_ratios: Array.isArray(values.falloff) ? values.falloff : null,
      bullet_drop: flight,
    };
  });
  return {
    id: weapon.id,
    name: weapon.gameName,
    section: weapon.section,
    family_id: weapon.familyId ?? null,
    zoom: weapon.adsFov > 0 ? Math.round((data.hipFov / weapon.adsFov) * 100) / 100 : null,
    source_url: `https://bayouindex.com/weapons/${weapon.id}/`,
    modes,
  };
});

// The public range module uses the largest modeled head-size drop range plus
// 50 m as its common chart bound. This evaluates to 221 m for this build.
const modeledHeadRanges = weapons.flatMap((weapon) => weapon.modes.map((mode) => {
  const flight = mode.bullet_drop;
  return flight ? Math.round(flight.zero_range_m + flight.muzzle_velocity_mps *
    Math.sqrt((2 * data.hunterHeadSize) / -flight.gravity_mps2)) : null;
}).filter((range) => range !== null));
const maxDistance = Math.max(...modeledHeadRanges) + 50;
if (data.buildId === "25344406" && maxDistance !== 221) throw new Error("Public model bound differs from reviewed 1865 pilot.");
const snapshot = {
  source_key: "bayou_index",
  build_id: data.buildId,
  generated_at: data.generatedAt,
  data_url: "https://bayouindex.com/_app/immutable/chunks/DvIjM2m5.js",
  data_sha256: sha256(dataPath),
  model_url: "https://bayouindex.com/_app/immutable/chunks/Bxa8H_2i.js",
  model_sha256: sha256(modelPath),
  max_distance_m: maxDistance,
  head_size_m: data.hunterHeadSize,
  reference_aim: "top_of_head",
  zone_offsets_m: { head: 0, upper_torso: .249, torso: .6646, legs: .8941 },
  weapons,
};
writeFileSync(outputPath, JSON.stringify(snapshot, null, 2) + "\n");
console.log(`${weapons.length} visible weapons; ${weapons.reduce((n, weapon) => n + weapon.modes.length, 0)} modes; ${maxDistance} m chart bound`);
