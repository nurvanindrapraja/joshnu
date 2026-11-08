<?php

namespace App\Services;

use App\Models\Galley;
use App\Models\Issue;
use App\Models\Publication;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Typesetting sederhana: metadata + abstrak → PDF via DomPDF (fallback
 * HTML-to-PDF sesuai PRD §11.1; Pandoc/LaTeX opsional bila terpasang).
 */
class PdfService
{
    public function generateArticlePdf(Publication $p, bool $isProof = false): Galley
    {
        $html = view('pdf.article', ['p' => $p, 'isProof' => $isProof])->render();
        $pdf = Pdf::loadHtml($html)->setPaper('a4');

        $target = 'publications/'.$p->id.'/('.($isProof ? 'proof' : 'article').').pdf';
        Storage::disk('local')->put($target, $pdf->output());

        $type = $isProof ? 'proof' : 'article_pdf';

        $galley = $p->galleys()->where('type', $type)->first();
        if ($galley) {
            $galley->update(['path' => $target, 'size_bytes' => Storage::disk('local')->size($target)]);
        } else {
            $galley = Galley::create([
                'publication_id' => $p->id,
                'type' => $type,
                'label' => $isProof ? 'PDF Proof' : 'PDF (Version of Record)',
                'path' => $target,
                'mime_type' => 'application/pdf',
                'size_bytes' => Storage::disk('local')->size($target),
                'is_version_of_record' => ! $isProof,
            ]);
        }

        return $galley;
    }

    public function generateIssuePdf(Issue $issue): string
    {
        $html = view('pdf.issue', ['issue' => $issue])->render();
        $pdf = Pdf::loadHtml($html)->setPaper('a4');

        $target = 'issues/'.$issue->id.'/issue.pdf';
        Storage::disk('local')->put($target, $pdf->output());

        return $target;
    }

    /** Render JATS XML string (dipakai OAI-PMH). */
    public function renderJatsInline(Publication $p): string
    {
        $meta = $p->metadataFor('en') ?? $p->metadataFor('id');
        $setting = \App\Models\JournalSetting::current();

        $contribs = $p->authors->map(fn ($a) =>
            '<contrib contrib-type="author"><name><surname>'.htmlspecialchars($a->name, ENT_XML1).'</surname></name>'.($a->orcid ? '<contrib-id contrib-id-type="orcid">'.$a->orcid.'</contrib-id>' : '').'</contrib>'
        )->implode('');

        $kwd = $meta->keywords
            ? '<kwd-group>'.collect($meta->keywords)->map(fn ($k) => '<keyword>'.htmlspecialchars($k, ENT_XML1).'</keyword>')->implode('').'</kwd-group>'
            : '';

        return '<article xmlns:xlink="http://www.w3.org/1999/xlink" article-type="research-article" dtd-version="1.2"><front><journal-meta><journal-id journal-id-type="issn">'.htmlspecialchars((string) $setting->issn_online, ENT_XML1).'</journal-id><journal-title-group><journal-title>'.htmlspecialchars($setting->name, ENT_XML1).'</journal-title></journal-title-group></journal-meta><article-meta><article-id pub-id-type="doi">'.htmlspecialchars((string) $p->doi, ENT_XML1).'</article-id><title-group><article-title>'.htmlspecialchars($meta->title, ENT_XML1).'</article-title></title-group><contrib-group>'.$contribs.'</contrib-group><abstract><p>'.htmlspecialchars($meta->abstract, ENT_XML1).'</p></abstract>'.$kwd.'</article-meta></front><body/></article>';
    }

    /** Export JATS XML per artikel (untuk deposit indeks/repository). */
    public function generateJatsXml(Publication $p): Galley
    {
        $meta = $p->metadataFor('en') ?? $p->metadataFor('id');
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE article><article xmlns:xlink="http://www.w3.org/1999/xlink" article-type="research-article" dtd-version="1.2"><front><journal-meta><journal-id journal-id-type="issn">'.\App\Models\JournalSetting::current()->issn_online.'</journal-id><journal-title-group><journal-title>'.\App\Models\JournalSetting::current()->name.'</journal-title></journal-title-group></journal-meta><article-meta><article-id pub-id-type="doi">'.$p->doi.'</article-id><title-group><article-title>'.$meta->title.'</article-title></title-group><contrib-group><contrib contrib-type="author">'.implode('', $p->authors->map(fn ($a) => '<name><surname>'.$a->name.'</surname></name>'.$a->orcid ? '<xref ref-type="auth" rid="orcid-'.$a->orcid.'"/>' : '').'</contrib>').'</contrib-group><abstract><p>'.$meta->abstract.'</p></abstract><kwd-group><keyword>'.implode('</keyword><keyword>', $meta->keywords ?? []).'</keyword></kwd-group></article-meta></front><body/><back><ack><p>CC BY 4.0</p></ack></back></article>');

        $target = 'publications/'.$p->id.'/article.xml';
        Storage::disk('local')->put($target, $xml->asXML());

        $galley = $p->galleys()->where('type', 'jats_xml')->first();
        if ($galley) {
            $galley->update(['path' => $target, 'size_bytes' => Storage::disk('local')->size($target)]);
        } else {
            $galley = Galley::create([
                'publication_id' => $p->id,
                'type' => 'jats_xml',
                'label' => 'JATS XML',
                'path' => $target,
                'mime_type' => 'application/xml',
                'size_bytes' => Storage::disk('local')->size($target),
            ]);
        }

        return $galley;
    }
}
