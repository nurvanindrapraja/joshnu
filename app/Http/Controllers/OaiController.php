<?php

namespace App\Http\Controllers;

use App\Models\JournalSetting;
use App\Models\Publication;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * OAI-PMH 2.0 (format oai_dc + jats).
 * Verbs: Identify, ListMetadataFormats, ListSets, ListIdentifiers, ListRecords, GetRecord.
 */
class OaiController extends Controller
{
    public function handle(Request $request): Response
    {
        $verb = $request->query('verb', '');
        $format = $request->query('metadataPrefix', 'oai_dc');
        $from = $request->query('from');
        $until = $request->query('until');
        $set = $request->query('set');
        $identifier = $request->query('identifier');

        $setting = JournalSetting::current();
        $repoId = 'oai:joshnu';
        $adminEmail = $setting->contact_email ?? 'admin@joshnu.example.org';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">';
        $xml .= '<responseDate>'.now()->toAtomString().'</responseDate>';
        $xml .= '<Request>'.$this->esc($request->fullUrl()).'</Request>';

        switch ($verb) {
            case 'Identify':
                $xml .= '<Identify>
                    <repositoryName>'.$this->esc($setting->name).'</repositoryName>
                    <baseURL>'.rtrim(config('app.url'), '/').'/oai</baseURL>
                    <protocolVersion>2.0</protocolVersion>
                    <adminEmail>'.$this->esc($adminEmail).'</adminEmail>
                    <earliestDatestamp>'.optional(
                        Publication::published()->min('published_at')
                    )->toAtomString() ?? now()->startOfMinute()->toAtomString().'</earliestDatestamp>
                    <deletedRecord>no</deletedRecord>
                    <granularity>YYYY-MM-DDThh:mm:ssZ</granularity>
                </Identify>';
                break;

            case 'ListMetadataFormats':
                $xml .= '<ListMetadataFormats><metadataFormat>
                    <metadataPrefix>oai_dc</metadataPrefix>
                    <schema>http://www.openarchives.org/OAI/2.0/oai_dc.xsd</schema>
                    <metadataNamespace>http://www.openarchives.org/OAI/2.0/oai_dc/</metadataNamespace>
                </metadataFormat><metadataFormat>
                    <metadataPrefix>jats</metadataPrefix>
                    <schema>http://jatl.org/publications/1.2/JATS-journalpublishing1-2.xsd</schema>
                    <metadataNamespace>http://jatl.org/publications/1.2</metadataNamespace>
                </metadataFormat></ListMetadataFormats>';
                break;

            case 'ListSets':
                $xml .= '<ListSets>';
                foreach (\App\Models\Issue::published()->get() as $issue) {
                    $xml .= '<set><setSpec>vol'.$issue->volume.'-no'.$issue->number.'</setSpec><setName>'.$this->esc($issue->label()).'</setName></set>';
                }
                $xml .= '</ListSets>';
                break;

            case 'ListIdentifiers':
            case 'ListRecords':
                [$records, $resumption] = $this->records($format, $from, $until, $set, $verb === 'ListRecords');
                $container = $verb === 'ListRecords' ? 'ListRecords' : 'ListIdentifiers';
                $item = $verb === 'ListRecords' ? 'record' : 'identifier';
                $xml .= '<'.$container.'>';
                foreach ($records as [$p, $ds]) {
                    $xml .= '<'.$item.'><header><identifier>'.$repoId.':'.$p->slug.'</identifier>';
                    $xml .= '<datestamp>'.$ds.'</datestamp>';
                    if ($set) {
                        $xml .= '<setSpec>'.$this->esc($set).'</setSpec>';
                    }
                    $xml .= '</header>';
                    if ($verb === 'ListRecords') {
                        $xml .= '<metadata>'.$this->metadata($p, $format).'</metadata>';
                    }
                    $xml .= '</'.$item.'>';
                }
                $xml .= '</'.$container.'>';
                break;

            case 'GetRecord':
                if (! $identifier) {
                    return $this->error($xml, 'badArgument', 'identifier wajib untuk GetRecord');
                }
                $slug = str_replace($repoId.':', '', $identifier);
                $p = Publication::published()->where('slug', $slug)->first();
                if (! $p) {
                    return $this->error($xml, 'idDoesNotExist', 'Record tidak ditemukan');
                }
                $ds = optional($p->published_at)->toAtomString() ?? now()->toAtomString();
                $xml .= '<GetRecord><record><header><identifier>'.$repoId.':'.$p->slug.'</identifier><datestamp>'.$ds.'</datestamp></header><metadata>'.$this->metadata($p, $format).'</metadata></record></GetRecord>';
                break;

            default:
                return $this->error($xml, 'badVerb', 'Verb tidak dikenal');
        }

        $xml .= '</OAI-PMH>';

        return response($xml, 200)->header('Content-Type', 'text/xml');
    }

    private function records(string $format, ?string $from, ?string $until, ?string $set, bool $withMetadata): array
    {
        $query = Publication::published()->with(['metadata', 'issue', 'section', 'submission.authors']);

        if ($from) {
            $query->where('published_at', '>=', $from);
        }
        if ($until) {
            $query->where('published_at', '<=', $until);
        }
        if (preg_match('/^vol(\d+)-no(\d+)$/', $set ?? '', $m)) {
            $query->whereHas('issue', fn ($i) => $i->where('volume', $m[1])->where('number', $m[2]));
        }

        $records = $query->orderBy('published_at')->limit(100)->get()
            ->map(fn ($p) => [$p, optional($p->published_at)->toAtomString() ?? now()->toAtomString()])
            ->all();

        return [$records, null];
    }

    private function metadata(\App\Models\Publication $p, string $format): string
    {
        if ($format === 'jats') {
            return (new \App\Services\PdfService())->renderJatsInline($p);
        }

        $meta = $p->metadataFor('en') ?? $p->metadataFor('id');
        $authors = $p->authors->pluck('name')->map(fn ($n) => $this->esc($n))->implode('</dc:creator><dc:creator>');

        return '<oai_dc:dc xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/" xmlns:dc="http://purl.org/dc/elements/1.1/">
            <dc:identifier>'.$this->esc($p->doi ?? 'oai:joshnu:'.$p->slug).'</dc:identifier>
            <dc:title>'.$this->esc($meta->title).'</dc:title>
            <dc:creator>'.$authors.'</dc:creator>
            <dc:subject>'.implode('</dc:subject><dc:subject>', array_map(fn ($k) => $this->esc($k), $meta->keywords ?? [])).'</dc:subject>
            <dc:description>'.$this->esc($meta->abstract).'</dc:description>
            <dc:date>'.optional($p->published_at)->format('Y-m-d').'</dc:date>
            <dc:type>text</dc:type>
            <dc:format>application/pdf</dc:type>
            <dc:publisher>'.$this->esc(\App\Models\JournalSetting::current()->name).'</dc:publisher>
            <dc:language>id</dc:language>
            <dc:rights>CC BY 4.0</dc:rights>
        </oai_dc:dc>';
    }

    private function esc(string $v): string
    {
        return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES);
    }

    private function error(string $partialXml, string $code, string $message): Response
    {
        $xml = $partialXml.'<error code="'.$code.'">'.$this->esc($message).'</error></OAI-PMH>';

        return response($xml, 400)->header('Content-Type', 'text/xml');
    }
}
