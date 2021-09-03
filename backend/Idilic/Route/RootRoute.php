<?php
namespace SeanMorris\Sycamore\Idilic\Route;

use \SeanMorris\Ids\Log;
use \SeanMorris\Ids\Routable;

class RootRoute implements Routable
{
	public function compileHtml($router)
	{
		$package = \SeanMorris\Ids\Package::getRoot();

		if($packagePublic = $package->publicDir())
		{
			$publicDir = $packagePublic->parent()->parent();
			$wrapperAssetDir = $publicDir->parent()->dir('mobile')->dir('assets');

			$indexHtml = $publicDir->file('index.html');

			$doc = new \DomDocument('4.0', 'UTF-8');
			$doc->loadHTML($indexHtml->slurp());
			$doc->substituteEntities = false;
			$doc->formatOutput = false;
			$doc->encoding = 'UTF-8';

			$xpath = new \DOMXpath($doc);

			$scripts = $xpath->query("//script[@src]");
			$styles  = $xpath->query("//link[@rel='stylesheet']");

			foreach($scripts as $scriptTag)
			{
				$src = $scriptTag->getAttribute('src');
				$srcUrl = parse_url($src);

				if(isset($srcUrl['scheme']))
				{
					continue;
				}

				$scriptFile = $publicDir->file($srcUrl['path']);

				if($scriptFile->check())
				{
					$scriptTag->removeAttribute('src');
					$scriptTag->setAttribute('type', 'text/javascript');

					$scriptTag->appendChild($doc->createTextNode('/*'));

					$source = $scriptFile->slurp();

					$source = str_replace('</script', '<\/script', $source);

					$scriptTag->appendChild(
						$doc->createComment('*/' . $source . '/*')
					);

					$scriptTag->appendChild($doc->createTextNode('*/'));
				}
			}

			foreach($styles as $styleTag)
			{
				$href = $styleTag->getAttribute('href');
				$hrefUrl = parse_url($href);

				if(isset($hrefUrl['scheme']))
				{
					continue;
				}

				$styleFile = $publicDir->file($hrefUrl['path']);

				if($styleFile->check())
				{
					$inlineStyle = $doc->createElement('style');
					$inlineStyle->setAttribute('type', 'text/css');
					$inlineStyle->appendChild($doc->createTextNode($styleFile->slurp()));

					$styleTag->parentNode->replaceChild($inlineStyle, $styleTag);
				}
			}

			$svgs = [
				// '/LETSVUE3-02.svg'
				// , '/connect.svg'
				// , '/bacon.svg'
				// , '/sausage.svg'
				// , '/server.svg'
				// , '/block.svg'
				// , '/delete.svg'
				// , '/edit.svg'
				// , '/more.svg'
				// , '/report.svg'
				// , '/results.svg'
				// , '/quarantine.svg'
				// , '/right-arrow.svg'
				// , '/left-arrow.svg'
				// , '/down-arrow.svg'
				// , '/up-arrow.svg'
				// , '/arrow-block-right.svg'
				// , '/arrow-block-left.svg'
				// , '/arrow-block-down.svg'
				// , '/arrow-block-up.svg'
				// , '/male-icon.svg'
				// , '/female-icon.svg'
				// , '/nonbinary-icon.svg'
			];

			$inlineStyle = $doc->createElement('style');
			$inlineStyle->setAttribute('type', 'text/css');

			$xpath->query("//head")->item(0)->appendChild($inlineStyle);

			foreach($svgs as $svg)
			{
				$svgFile = $publicDir->file($svg);

				$svgStyle = sprintf(
					<<<ENDCSS
					[data-src="%s"] {
						background-image: url("data:image/svg+xml;utf8,%s");
					}
					ENDCSS
					, $svg
					, rawurlencode($svgFile->slurp())
				);

				$inlineStyle->appendChild($doc->createTextNode($svgStyle));
			}

			$appFile = $wrapperAssetDir->file('app.html');

			$source = $doc->saveHTML($xpath->query('/')->item(0));

			printf('Writing to %s...', $appFile);

			$appFile->write($source, FALSE);
		}

		Log::error($packagePublic);
	}
}
