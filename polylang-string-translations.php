<?php
/**
 *
"Polylang String Translations" is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

"Polylang String Translations" is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with "Polylang String Translations". If not, see https://www.gnu.org/licenses/gpl-3.0.html.
 *
 * Plugin Name: Polylang String Translations
 * Description: A simple plugin allowing to translate theme strings (when using Polylang plugin).
 * Version: 1.0
 * Author: Ivan Hanák <kontakt@hanakivan.sk>
 * Author URI: https://hanakivan.sk
 * Text Domain: pst
 * Requires at least: 5.4
 * Requires PHP: 7.4
 */


require_once dirname(__FILE__)."/.src/vendor/autoload.php";


use Gettext\Loader\PoLoader;
use Gettext\Generator\MoGenerator;
use Gettext\Translation;
use Gettext\Generator\PoGenerator;



add_action('admin_menu', function (): void {
	add_submenu_page(
		'mlang',
		__( 'String translations' ),
		__( 'String translations' ),
		'manage_options',
		'pll-string-translations',
		'jp_string_translations_render',
		2
	);
},999);

/**
 * Display callback for the submenu page.
 */
function jp_string_translations_render(): void {
	$map = [
		'en' => 'en_US',
		'cs' => 'cs_CZ'
	];

	$labelMap = [
		'en' => 'English',
		'cs' => 'Czech'
	];

	$curLang = pll_current_language();

	if(!$curLang) {
		print "<div style='padding: 20px; background-color: #fff3f3; margin-top: 20px;'>Please, select a language to translate.</div>";
        return;
	}

	$fileName = $map[pll_current_language()];

	$loader = new PoLoader();
	$translations = $loader->loadFile(get_template_directory()."/languages/{$fileName}.po");

	$list = collect($translations->getTranslations())->groupBy(function (Translation $translation) {
		return $translation->getContext();
	});

	$headers = $list->keys();
	$tab = isset($_GET['tab']) ? $_GET['tab'] : $headers->first();

	?>

	<style>
        .jp-string-translation {
            padding: 20px;
            border: 1px solid #c3c4c7;
            border-top: none;
        }

        .jp-string-translation-wrapper {
            background-color: white;
            display: block;
            line-height: 1.8;
            font-size: 12px;
            padding: 12px;
        }

        .jp-string-translation-wrapper + .jp-string-translation-wrapper {
            margin-top: 30px;
        }

        .jp-string-translation-label {
            font-weight: normal;
            font-family: monospace;
            display: block;
            margin-bottom: .4em;
        }

        .jp-string-translation-text-field {
            display: block;
            width: 100%;
            border-color: rgba(0,0,0,.3) !important;
            box-shadow: none!important;
            background-color: rgba(0,0,0,.04)!important;
            font-size: 13px;
            transition: all 199ms ease-out;
        }

        .jp-string-translation-text-field:focus {
            border-color: rgba(0,0,0,.3) !important;
            outline: 3px solid rgba(0, 128, 0, 0.2) !important;
            background-color: white!important;
        }

        textarea.jp-string-translation-text-field {
            resize: vertical;
            padding: 10px;
            line-height: 1.4;
        }

        .jp-string-translation-text-field::placeholder  {
            color: rgba(0,0,0,.3);
        }
	</style>
	<nav class="nav-tab-wrapper">
		<?php foreach($headers as $header):?>
			<a href="<?php admin_url('admin.php');?>?page=pll-string-translations&amp;tab=<?php echo $header;?>" class="nav-tab <?php if($tab===$header):?>nav-tab-active<?php endif; ?>"><?php echo ucfirst($header);?></a>
		<?php endforeach;?>
	</nav>

	<div class="tab-content jp-string-translation">
		<h3 style="margin-top: 0">Strings to translate in <code><?php echo $labelMap[$curLang];?></code></h3>

		<form action="<?php echo admin_url('admin.php');?>" method="post">
			<fieldset>
				<?php
				/**
				 * @var Translation $translation
				 */
				foreach($list->get($tab) as $translation):?>
					<div class="jp-string-translation-wrapper">
						<strong class="jp-string-translation-label"><?php echo esc_attr($translation->getOriginal());?></strong>
						<?php if(mb_strlen($translation->getOriginal()) > 100):?>
							<textarea name="translations[<?php echo esc_attr($translation->getId());?>]" placeholder="Enter translated text..." class="text jp-string-translation-text-field"><?php echo esc_attr($translation->getTranslation());?></textarea>
						<?php else :?>
							<input name="translations[<?php echo esc_attr($translation->getId());?>]" type="text" value="<?php echo esc_attr($translation->getTranslation());?>" placeholder="Enter translated text..." class="text jp-string-translation-text-field" />
						<?php endif;?>
					</div>
				<?php endforeach;?>
			</fieldset>

			<div style="margin-top: 2em">
				<button type="submit" class="button button-primary">Save</button>
			</div>

			<input type="hidden" name="context" value="<?php echo esc_attr($tab);?>" />
			<input type="hidden" name="action" value="pllstringtranslations" />
		</form>
	</div>
	<?php
}

add_action( 'admin_action_pllstringtranslations', function (): void {
	$map = [
		'en' => 'en_US',
		'cs' => 'cs_CZ'
	];

	$curLang = pll_current_language();

	$fileName = $map[pll_current_language()];

	$context = $_POST['context'];
	$fields = stripslashes_deep($_POST['translations']);

	$loader = new PoLoader();
	$translations = $loader->loadFile(get_template_directory()."/languages/{$fileName}.po");

	$list = collect($translations->getTranslations())->groupBy(function (Translation $translation) {
		return $translation->getContext();
	})->get($context);

	$list = $list->keyBy(fn (Translation $translation) =>$translation->getId());

	foreach($fields as $id => $translated) {

		/**
		 * @var Translation $object
		 */
		$object = $list->get($id);
		$object->translate($translated);
	}

	$poGenerator = new PoGenerator();
	$poGenerator->generateFile($translations, get_template_directory()."/languages/{$fileName}.po");

	$moGenerator = new MoGenerator();
	$moGenerator->generateFile($translations, get_template_directory()."/languages/{$fileName}.mo");

	wp_redirect('admin.php?page=pll-string-translations&tab='.$context);
	exit;
} );


