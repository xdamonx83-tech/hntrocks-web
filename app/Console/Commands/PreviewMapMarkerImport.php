<?php

namespace App\Console\Commands;

use App\Services\Maps\Imports\MapMarkerImportPreview;
use App\Services\Maps\Imports\MapMarkerImportProviderRegistry;
use App\Services\Maps\Imports\SourceFormatException;
use App\Services\Maps\Imports\SourceUnavailableException;
use Illuminate\Console\Command;
use InvalidArgumentException;

final class PreviewMapMarkerImport extends Command
{
    protected $signature = 'hnt:maps:marker-import-preview
        {--provider=kamille : External marker provider ID}
        {--all-maps : Select all maps offered by the provider}
        {--map=* : Select a map by HNT slug; repeatable}
        {--all-supported : Select all supported import categories}
        {--category=* : Select a category or subtype; repeatable}
        {--mode=sync : sync or add_only}
        {--json : Emit machine-readable JSON}';

    protected $description = 'READ ONLY / DRY RUN: preview external HNT map markers without database writes';

    public function handle(MapMarkerImportProviderRegistry $providers, MapMarkerImportPreview $preview): int
    {
        try {
            $provider = $providers->get((string) $this->option('provider'));
            $maps = $this->option('all-maps') ? array_keys($provider->maps()) : $this->option('map');
            $categories = $this->option('all-supported') ? array_keys($provider->categories()) : $this->option('category');
            // The current source distinguishes these two wild-target subtypes; select both explicitly.
            if ($this->option('all-supported') && in_array('wild_target', $categories, true)) {
                $categories = array_values(array_diff($categories, ['wild_target']));
                array_push($categories, 'wild_target:rotjaw', 'wild_target:hellborn');
            }
            $result = $preview->create($provider, $maps, $categories, (string) $this->option('mode'));
        } catch (SourceUnavailableException $exception) {
            $this->error('READ ONLY / DRY RUN: Die externe Markerquelle konnte nicht geladen werden.');
            return self::FAILURE;
        } catch (SourceFormatException $exception) {
            $this->error('READ ONLY / DRY RUN: Das Datenformat der Quelle hat sich möglicherweise geändert.');
            return self::FAILURE;
        } catch (InvalidArgumentException $exception) {
            $this->error('READ ONLY / DRY RUN: Ungültiger Provider, Map, Kategorie oder Modus.');
            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode([
                'status' => 'READ ONLY / DRY RUN',
                'result' => $result,
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        }

        $this->info('READ ONLY / DRY RUN');
        $this->line($result['provider_name'].' · '.$result['mode']);
        foreach ($result['maps'] as $slug => $map) {
            $this->newLine();
            $this->line($map['name'].' ('.$slug.')');
            $table = [];
            foreach ($map['categories'] as $category => $counts) {
                $table[] = [$category, $counts['new'], $counts['changed'], $counts['unchanged'],
                    $counts['removed_external'], $counts['unclassified'], $counts['out_of_bounds'], $counts['protected']];
            }
            $this->table(['Kategorie', 'Neu', 'Geändert', 'Unverändert', 'Extern fehlend', 'Unklassifiziert', 'Außerhalb', 'Geschützt'], $table);
            $this->line('Legacy: tower '.$map['legacy']['tower'].' · bugs '.$map['legacy']['bugs'].' · wild '.$map['legacy']['wild']);
            $protected = $map['protected_existing'];
            $this->line('Cash-Schutz: cash '.$protected['cash'].' · submission-Schlüssel '.$protected['submission_key']
                .' · verknüpfte Submissions '.$protected['linked_submission'].' · geschützt gesamt '.$protected['total_unique']);
        }
        $total = $result['total'];
        $this->newLine();
        $this->line('Gesamt: neu '.$total['new'].' · geändert '.$total['changed'].' · unverändert '.$total['unchanged']
            .' · extern fehlend '.$total['removed_external'].' · unklassifiziert '.$total['unclassified']
            .' · außerhalb '.$total['out_of_bounds'].' · geschützt '.$total['protected']);

        return self::SUCCESS;
    }
}
