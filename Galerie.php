<?php
namespace Galerie;
include_once("wideimage-11.02.19-full/lib/WideImage.php");
class Galerie {
	const FORM_ANNULE = -1;
	const FORM_ERREUR = -2;
	const FORM_OK = 1;

	public $classPhoto;
	/** @var string - Le chemin vers le dossier qui contient les images */
	public $dossier;	// Le chemin vers les images
	/** @var string - Le chemin vers le dossier qui contient les images */
	public $root;	// Le chemin vers les images
	/** @var string - Le chemin vers les images à partir du dossier */
	public $sousdossier;
	public $versionsImages = array();
	public $admin = false;
	public $idLac;
	public $lac;
	//static public $angleLimite = 20;
	static public $nomsMois = array(1=>"Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre");
	static public $nomsJours = array("Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi");
	public function __construct($lac, $props=array()) {
		if (!is_object($lac)) {
			$fiche = new Fiche($lac);
			$lac = $fiche->lac;
		}
		$this->lac = $lac;
		$this->idLac = $lac->id;
		$this->dossier = Cre::$path_media;
		$this->root = Cre::$url_media;
		$this->admin = Cre::$admin;
	}
	public function appliquerProps($props) {
		foreach ($props as $nomprop=>$valprop) {
			$this->$nomprop = $valprop;
		}
		return $this;
	}
	public function chemin($sub=null) {
		$resultat[] = $this->dossier;
		$resultat[] = $this->sousdossier;
		$resultat[] = $this->idLac;
		if ($sub) $resultat[] = $sub;
		$resultat = implode('/', $resultat);
		return $resultat;
	}
	public function url($sub=null) {
		$resultat[] = $this->root;
		$resultat[] = $this->sousdossier;
		$resultat[] = $this->idLac;
		if ($sub) $resultat[] = $sub;
		$resultat = implode('/', $resultat);
		return $resultat;
	}

	/** extension
	 * Retourne l'extension d'un nom de fichier envoyé en paramètre. Ex.: chat.jpg retourne jpg
	 * @param string $fichier - Le nom (ou le chemin) du fichier à traiter
	 * @return string - L'extension trouvée ou '' s'il n'y en a pas
	 */
	static public function extension($fichier) {
		debug();
		$pos = strrpos($fichier, '.');
		if ($pos===false) return $fichier;
		return substr($fichier, $pos+1);
	}

	/** toJson
	 * Retourne les infos en format json
	 */
	public function toJson() {
		debug($this);
	}

	/** aller
	 * Effectue une redirection vers la page envoyée en paramètre
	 * @param string $page L'URL de la page de destination. Défaut: "index.php"
	 * @return void - Ne retourne rien
	 */
	static public function aller($page="index.php") {
		header("location:$page");
		exit;
	}

	/** ecrireInfos
	 * Écrit le fichier d'infos d'une photo.
	 * @param array $infos - Le array contenant les informations de la photo
	 * @return void - Ne retourne rien
	 * @note Le nom à donner au fichier est contenu dans le array $infos
	 * @note Voir les spécifications du fichier infos pour le format final
	 */
	static public function ecrireInfos($infos) {
		$fichier = $infos['fichier'];
		$resultat = array();
		foreach($infos as $nom=>$info) {
			$resultat[] = $nom.":".$info;
		}
		$resultat = implode("\r\n", $resultat);
		file_put_contents("infos/".$fichier.".txt", $resultat);
	}

	/** lireInfos
	 * Lit un fichier d'informations et retourne le array correspondant
	 * @param string $fichier - Le nom du fichier à récupérer (sans le nom du sous-dossier)
	 * @param boolean $complet - Indique si le nom fourni est complet (avec le sous-dossier). Défaut: false.
	 * @return array - Le array contenant les informations
	 * @note Voir les spécifications du fichier infos pour le format final
	 */
	static public function lireInfos($fichier, $complet=false) {
		if (!$complet) $fichier = "infos/".$fichier.".txt";
		$infosbrut = file_get_contents($fichier);
		$infosbrut = explode("\r\n", $infosbrut);
		$resultat = array();
		foreach($infosbrut as $info) {
			$pos = strpos($info, ":");
			$nom = substr($info, 0, $pos);
			$val = substr($info, $pos+1);
			$resultat[$nom] = $val;
		}
		return $resultat;
	}

	/** recupererInfosPhotoGet
	 * Retourne les informations relatives à une photo dont le nom devrait se trouver
	 *   dans l'adresse (?fichier=...). Retourne à la page d'accueil si la donnée
	 *   est absente. Retourne également à la page d'accueil si le fichier .txt
	 *   correspondant n'existe pas.
	 * @uses aller
	 * @uses lireInfos
	 * @return void - Ne retourne rien
	 * @return array - Le array contenant les informations
	 */
	static public function recupererInfosPhotoGet() {
		if (!isset($_GET['fichier'])) {
			static::aller();
		}
		$fichier = $_GET['fichier'];
		if (!file_exists("infos/$fichier.txt")) {
			static::aller();
		}
		return static::lireInfos($fichier);
	}

	/** traiterFormulaire
	 * Lance le traitement d'un formulaire en fonction de la présence du bon champ de control
	 * @uses traiterFormulaireAjouter
	 * @uses traiterFormulaireModifier
	 * @uses traiterFormulaireSupprimer
	 * @return Photo - Ne retourne rien
	 */
	static public function traiterFormulaire() {
		// Vérifie et traite l'annulation
		if (isset($_POST['annuler'])) return static::FORM_ANNULE;
		$resultat = '';
		if (isset($_POST['action']) && $action=$_POST['action']) {
			if ($action == static::$ACTION_AJOUTER) $resultat = static::traiterFormulaireAjouter();
			elseif ($action == static::$ACTION_MODIFIER) $resultat = static::traiterFormulaireModifier();
		} else if (isset($_GET[static::$ACTION_SUPPRIMER])) {
			$resultat = static::traiterLienSupprimer();
		} else if (isset($_GET[static::$ACTION_MODIFIER])) {
			$resultat = static::traiterLienModifier();
		}
		/** @var $resultat Galerie */
		if (is_object($resultat)) {
			return $resultat->html_apercu();
		}
		if (is_string($resultat)) {
			return $resultat;
		}
		return json_encode($resultat);
	}

	/** traiterLienModifier
	 * Effectue le traitement d'un formulaire de suppression.
	 * @uses aller
	 * @uses supprimerPhoto
	 * @return void - Ne retourne rien
	 * @note Les données sont tirées de la variable superglobale $_POST
	 */
	static public function traiterLienModifier() {
		$idPhoto = $_GET[static::$ACTION_MODIFIER];
		$g = new static($_GET['idLac']);
		$p = new $g->classPhoto($g, $idPhoto);
		// On supprime les fichiers.
		$resultat = $p->lireInfos()->toJson();
		return $resultat;
	}
	static public function mkdir($dir) {
		if (file_exists($dir)) return;
		$parent = dirname($dir);
		if (!file_exists($parent)) static::mkdir($parent);
		mkdir($dir);
	}

	//////////////////////////////////////////////////////////////////////////////
	// CODES D'AFFICHAGE /////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////
	/** formaterDate
	 * Retourne une date formatée pour l'affichage.
	 * @param integer $timestamp - Le timestamp de la date à afficher
	 * @uses $nomsMois - Un tableau des noms de mois en français. 1=>"Janvier" et 12=>"Décembre"
	 * @uses $nomsJours - Un tableau des noms de jours en français. 0=>"Dimanche" et 7=>"Samedi"
	 * @return string - Une date sous le format "mardi 31 mars 2023"
	 * @note Utiliser la fonction date() pour connaître les jour, mois et année
	 *   correspondant au timestamp
	 * @note Les noms des mois et des jours dans le array ont une majuscule,
	 *   mais l'affichage doit être en minuscule.
	 */
	static public function formaterDate($timestamp) {
		$nomsMois = static::$nomsMois;
		$nomsJours = static::$nomsJours;
		$jour = intval(date("j", $timestamp));
		$mois = strtolower($nomsMois[date("n", $timestamp)]);
		$nomJour = strtolower($nomsJours[date("w", $timestamp)]);
		$an = intval(date("Y", $timestamp));
		if ($jour == 1) $jour .= '<sup>er</sup>';
		return "$nomJour $jour $mois $an";
	}

	/** html_formAjouter
	 * Retourne le HTML du formulaire d'ajout
	 * @uses html_champAuteur
	 * @uses html_champPhoto
	 * @uses html_champTitre
	 * @uses html_champDescription
	 * @uses html_champDateAjout
	 * @return string - Le HTML de la balise form
	 * @note Utiliser la fonction time() pour faire afficher la date actuelle.
	 * @note Pour faciliter les tests, vous pouvez mettre des valeurs bidons comme paramètres des méthodes.
	 */
	public function html_formAjouter() {
		$photo = new $this->classPhoto($this);
		$resultat = '';
		$resultat .= $photo->html_formAjouter();
		return $resultat;
	}

	/** page
	 * Retourne le HTML entier d'une page dont on fournit le contenu de la
	 * balise div.contenu ainsi que le title
	 * @param string $contenu - Le contenu HTML de la balise div.contenu
	 * @param string $title - Le titre de la page
	 * @uses page_avant
	 * @uses page_apres
	 * @return string - Le HTML d'une page.
	 */
	static public function page($contenu, $title="") {
		$resultat = '';
		$resultat .= static::page_avant($title);
		$resultat .= $contenu;
		$resultat .= static::page_apres();
		return $resultat;
	}

	/** page_avant
	 * Retourne tout le début de la page jusqu'à la balise div.contenu INCLUSIVEMENT.
	 * @param string $title - Le contenu de la balise title. Défaut: ""
	 * @return string
	 * @note On ajoute le suffixe " &mdash; Galerie" au titre fournit. Si le titre est vide, on n'affiche que Galerie.
	 */
	static public function page_avant($title="") {
		debug("Utile!");
		$menu = new Menu();
		$menu->ajouter(new Menu('Accueil','index.php'))->ajouter(new Menu('Ajouter','ajouter.php'));
		// Plus bas, utiliser $menu->html() pour obtenir le HTML du menu

		$resultat = '';
		$resultat .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
		$resultat .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">';
		$resultat .= '<head>';
		$resultat .= '<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />';
		if ($title == '') $resultat .= '<title>Galerie</title>';
		else $resultat .= '<title>'.$title.' &mdash; Galerie</title>';
		$resultat .= '<link rel="stylesheet" type="text/css" href="galerie.css" media="all" />';
		$resultat .= '</head>';
		$resultat .= '<body>';
		$resultat .= '<div class="header">';
		if ($title == '') $resultat .= '<h1>Galerie</h1>';
		else $resultat .= '<h1>'.$title.'</h1>';
		$resultat .= '<div class="nav">'.$menu->html().'</div>';
		$resultat .= '</div>';
		$resultat .= '<div class="interface">';
		$resultat .= '<div class="contenu">';
		return $resultat;
	}

	/** page_apres
	 * Retourne le HTML de la fin de la page à partir de la balise fermante du div.contenu INCLUSIVEMENT.
	 * @return string - Le HTML du bas de la page
	 */
	static public function page_apres() {
		$resultat = '';
		$resultat .= '</div>';
		$resultat .= '</div>';
		$resultat .= '<div class="footer">Galerie, Galera. Fait par <b>Martin Boudreau</b> dans le cadre du cours <cite>Intégration Web III</cite></div>';
		$resultat .= '</body>';
		$resultat .= '</html>';
		return $resultat;
	}
	/**
	 * Active ou désactive un fichier en mettant un "_"
	 * @param type $fic
	 * @param type $etat
	 * @return string
	 */
	static public function activerFic($fic, $etat=true) {
		$dir = dirname($fic);
		$nom = basename($fic);
		$fic2 = $dir."/_".$nom;
		if ($etat) {
			rename($fic2, $fic);
		} else {
			rename($fic, $fic2);
		}
		return $dir."/_".$nom;
	}
	static public function supprimerFicsArray($fics) {
		foreach($fics as $fic) {
			unlink($fic);
		}
	}
	static public function deplacerFics($origine, $dest) {
		$fics = glob($origine."/*");
		foreach($fics as $fic) {
			$nom = basename($fic);
			rename($fic, $dest."/".$nom);
		}
	}
}
