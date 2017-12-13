<?php
namespace Galerie;
class Photo {
	static public $extension="png";
	/** @var Galerie $galerie */
	public $galerie;
	public $id = '';
	public $dateAjout = '';
	public function __construct($galerie, $id='') {
		$this->galerie = $galerie;
		$this->id = $id;
		if (!$this->id) $this->id = uniqid();
	}
	/**
	 * Retourne le chemin complet d'un fichier en fonction du type envoyé
	 * @param string $type
	 * @return string
	 */
	public function chemin($type='') {
		$nomFic = $this->nomFicType($type);
		return $this->galerie->chemin($nomFic);
	}
	/**
	 * Retourne le url d'un fichier en fonction du type envoyé
	 * @param string $type
	 * @return string
	 */
	public function url($type='') {
		$nomFic = $this->nomFicType($type);
		return $this->galerie->url($nomFic);
	}
	/**
	 * Compose le nom d'un fichier
	 * @param string $extension
	 * @param string $suffixe
	 * @param string $prefixe
	 * @return string
	 */
	public function nomFic($extension="", $suffixe="", $prefixe="") {
		if (!$extension) $extension = static::$extension;
		$prefixe .= $this->galerie->lac->id;
		if ($this->galerie->lac->topo_prefixe) $prefixe .= " ".$this->galerie->lac->topo_prefixe;
		$prefixe .= " ".$this->galerie->lac->name;
		if ($this->galerie->lac->topo_suffixe) $prefixe .= " ".$this->galerie->lac->topo_suffixe;
		if ($this->galerie->lac->topo_adj) $prefixe .= " ".$this->galerie->lac->topo_adj;
		$prefixe = Cre::normaliser($prefixe);
		return static::nomFicId($this->id, $extension, $suffixe, $prefixe);
	}
	static public function nomFicId($id, $extension="", $suffixe="", $prefixe="") {
		if (!$extension) $extension = static::$extension;
		$resultat = $id;
		if ($suffixe) $resultat .= "_".$suffixe;
		if ($prefixe) $resultat = $prefixe."_".$resultat;
		$resultat .= ".".$extension;
		return $resultat;
	}

	/** gererFichier
	 * Gère l'arrivée d'une photo : Crée l'original et crée l'apercu
	 * @param string $path - Le chemin ou se trouve l'original (c'est le tmp_name)
	 * @return \Photo
	 */
	public function gererFichier($path) {
		$g = $this->galerie;
		$dossier = $g->chemin();
		$g->mkdir($dossier);
    $image = WideImage::load($path);
		foreach ($g->versionsImages as $suffixe=>$format) {
			$destination = $this->chemin($suffixe);
			$format->traiter($image, $destination);
		}
		return $this;
	}

	/** supprimerPhoto
	 * Supprime les 3 fichiers relatifs à une photo : la photo, l'apercu et le fichier d'infos.
	 * @param string $fichier - Le nom du fichier de la photo
	 * @return void - Ne retourne rien
	 * @note Ne pas oublier de vérifier la présence d'un fichier avant de le supprimer.
	 */
	public function supprimer() {
		if (!file_exists($this->chemin('infos'))) return false;
		$this->supprimerFic($this->chemin('infos'));
		$this->supprimerFic($this->chemin('apercu'));
		$this->supprimerFic($this->chemin('icone'));
		$this->supprimerFic($this->chemin());
		return true;
	}
	/** supprimerPhoto
	 * Supprime les 3 fichiers relatifs à une photo : la photo, l'apercu et le fichier d'infos.
	 * @param string $fichier - Le nom du fichier de la photo
	 * @return void - Ne retourne rien
	 * @note Ne pas oublier de vérifier la présence d'un fichier avant de le supprimer.
	 */
	public function supprimerTout() {
		$glob = glob($this->chemin('glob'));
		foreach ($glob as $fic) {
			unlink($fic);
		}
		return true;
	}
	/** supprimerPhoto
	 * Supprime les 3 fichiers relatifs à une photo : la photo, l'apercu et le fichier d'infos.
	 * @param string $fichier - Le nom du fichier de la photo
	 * @return void - Ne retourne rien
	 * @note Ne pas oublier de vérifier la présence d'un fichier avant de le supprimer.
	 */
	public function supprimerFic($path) {
		if (file_exists($path)) unlink($path);
		return $this;
	}
	/** fromArray
	 * Renseigne les proprietes de la photo en fonction d'un array.
	 * @return array - Les infos
	 */
	public function fromArray($infos) {
		foreach ($infos as $nom=>$val) {
			if (property_exists($this, $nom)) {
				$this->$nom = $val;
			}
		}
		return $this;
	}
	/** toArray
	 * Retourne un array représentant la photo.
	 * @return array - Les infos
	 */
	public function toArray() {
		$resultat = array();
		$resultat['id'] = $this->id;
		if (!empty($this->galerie)) $resultat['idLac'] = $this->galerie->idLac;
		$resultat['dateAjout'] = $this->dateAjout;
		return $resultat;
	}

	/** toJson
	 * Retourne les infos en format json
	 */
	public function toJson() {
		$resultat = $this->toArray();
		return json_encode($resultat);
	}
	/** EcrireInfos
	 * Lit un fichier d'informations et retourne le array correspondant
	 * @return \Photo - this
	 */
	public function ecrireInfos() {
		$infos = $this->toArray();
		$infos = var_export($infos, true);
		file_put_contents($this->chemin('infos'), "<?php\r\n \$infos = $infos; \r\n?".">");
		return $this;
	}

	/** lireInfos
	 * Lit un fichier d'informations et retourne le array correspondant
	 * @return \Photo - this
	 */
	public function lireInfos($path_infos=null) {
		if (is_null($path_infos)) $path_infos = $this->chemin('infos');
		include $path_infos;
		if (!empty($this->id)) unset($infos['id']);
		$this->fromArray($infos);
		return $this;
	}

	/**?????? traiterFormulaireAjouter
	 * Effectue le traitement d'un formulaire d'ajout.
	 * @return void - Ne retourne rien
	 */
	public function recupererInfosPost() {
		debug($_POST);
		$resultat = array();
		// On récupère les informations du POST pour créer $infos
		$resultat['original'] = $_FILES['photo']['name'];
		if (isset($_POST['fichier'])) {
			$resultat['fichier'] = $_POST['fichier'];
		} else {
			$resultat['fichier'] = static::idFichier($resultat['original']);
		}
		if (isset($_POST['dateAjout_an'])) {
			$dateAjout = $_POST['dateAjout_an']."-".$_POST['dateAjout_mois']."-".$_POST['dateAjout_jour'];
			$resultat['dateAjout'] = strtotime($dateAjout);
		} else {
			$resultat['dateAjout'] = time();
		}
		return $resultat;
	}
	/**?????? traiterFormulaireAjouter
	 * Effectue le traitement d'un formulaire d'ajout.
	 * @return void - Ne retourne rien
	 */
	public function traiterFormulaireAjouter() {
		// On récupère les informations du POST pour créer $infos
		$infos = $this->recupererInfosPost();
		$photo = $_FILES['photo'];
		// S'il n'y a pas de photo (dans $_FILES), on retourne à la page d'index
		if (!is_uploaded_file($photo['tmp_name'])) static::aller();
		// On déplace/crée les fichiers de la photo
		static::deplacer($photo['tmp_name'], $infos['fichier']);
		// On écrit les infos dans son fichier.
		static::ecrireInfos();
		// On redirige vers la page d'accueil.
		static::aller();
	}
	/** traiterFormulaireModifier
	 * Effectue le traitement d'un formulaire de modification.
	 * @uses aller
	 * @uses deplacer
	 * @uses ecrireInfos
	 * @return void - Ne retourne rien
	 * @note Les données sont tirées des variables superglobales $_POST et $_FILES
	 * @note On applique la fonction nl2br à la description avec true comme 2e paramètre
	 * @note Si l'usager n'a pas envoyé de photo, on laisse celle-ci intacte.
	 *   On ne fait alors que changer les informations textuelles
	 * @note Il est normal que la photo ne change pas lors des tests. C'est parce
	 *   qu'une photo du même nom se trouve dans le cache du fureteur. Il faut
	 *   alors recharger la page (parfois plusieurs fois) pour forcer le fureteur
	 *   à charger la nouvelle photo.
	 */
	static public function traiterFormulaireModifier() {
		// On récupère les informations du POST pour créer $infos
		$infos = $this->recupererInfosPost();
		if (isset($_POST['annuler'])) static::aller("photo.php?fichier=".$infos['fichier']."");
		$photo = $_FILES['photo'];
		// S'il n'y a pas de photo, on garde la photo originale et on traite les informations
		if (is_uploaded_file($photo['tmp_name'])) {	// On change la photo
			// On déplace/crée les fichiers de la photo au besoin
			static::deplacer($photo['tmp_name'], $infos['fichier']);
		}
		// On écrit les infos dans son fichier.
		static::ecrireInfos();
		// On redirige vers la page de détails en fournissant le nom du fichier.
		static::aller("photo.php?fichier=$fichier");
	}
	/** traiterFormulaireSupprimer
	 * Effectue le traitement d'un formulaire de suppression.
	 * @uses aller
	 * @uses supprimerPhoto
	 * @return void - Ne retourne rien
	 * @note Les données sont tirées de la variable superglobale $_POST
	 */
	static public function traiterFormulaireSupprimer() {
		// On vérifie et traite l'annulation Retour à la page de détails de la photo
		$fichier = $_POST['fichier'];
		if (isset($_POST['annuler'])) static::aller("photo.php?fichier=".$fichier."");
		// On supprime les fichiers.
		static::supprimerPhoto($fichier);
		// On redirige vers la page d'accueil.
		static::aller();
	}

	//////////////////////////////////////////////////////////////////////////////
	// CODES D'AFFICHAGE /////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////

	/** html_formModifier
	 * Retourne le HTML du formulaire de modification avec les bonnes données
	 *   en fonction du array $infos passé en paramètre
	 * @param array $infos - Les informations de la photo à modifier.
	 * @uses html_apercuPhoto
	 * @uses html_champAuteur
	 * @uses html_champPhoto
	 * @uses html_champTitre
	 * @uses html_champDescription
	 * @uses html_champFichier
	 * @uses html_champDateAjout
	 * @return string - Le HTML de la balise form
	 */
	public function html_formModifier() {
		$resultat = '';
		$resultat .= '<div class="clearfix">';
		$resultat .= $this->html_apercu();
		$resultat .= '<h2>Modifier cette photo ?</h2>';
		$resultat .= $this->html_form();
		$resultat .= '</div>';
		return $resultat;
	}

	/** html_formSupprimer
	 * Retourne le HTML du formulaire de suppression de la photo dont le array
	 *   $infos est passé en paramètre
	 * @param array $infos - Les informations de la photo à potentiellement supprimer.
	 * @uses html_apercuPhoto
	 * @uses html_champFichier
	 * @return string - Le HTML de la balise form
	 */
	public function html_formSupprimer($infos) {
		$resultat = '';
		$resultat .= '<form action="" method="post" enctype="multipart/form-data">';
		$resultat .= '<div class="clearfix">';
		$resultat .= static::html_apercuPhoto($infos);
		$resultat .= '<h2>Voulez-vous vraiment supprimer cette photo ?</h2>';
		$resultat .= '</div>';
		$resultat .= static::html_champFichier($infos['fichier']);
		$resultat .= '<div class="boutons"><input type="hidden" name="supprimer" /><input type="submit" value="Oui" /><input type="submit" value="Non" name="annuler" /></div>';
		$resultat .= '</form>';
		return $resultat;
	}

	/** html_champTitre
	 * Le champ "titre". Retourne le HTML de la ligne du formulaire (div).
	 * @param string $value - La valeur à mettre dans l'attribut "value" du champ de formulaire. Défaut: "".
	 * @return string
	 * @note On doit appliquer la fonction htmlspecialchars à la valeur.
	 */
	public function html_champTitre() {
		return '<div><label for="titre">Titre</label><div><input type="text" name="titre" id="titre" value="'.htmlspecialchars($this->titre).'" /></div></div>';
	}

	/** html_champAuteur
	 * Le champ "auteur". Retourne le HTML de la ligne du formulaire (div).
	 * @param string $value - La valeur à mettre dans l'attribut "value" du champ de formulaire. Défaut: "".
	 * @return string
	 * @note On doit appliquer la fonction htmlspecialchars à la valeur.
	 */
	public function html_champAuteur() {
		return '<div><label for="titre">Auteur</label><div><input type="text" name="auteur" id="auteur" value="'.htmlspecialchars($this->auteur).'" /></div></div>';
	}

	/** html_champDescription
	 * Le champ "description". Retourne le HTML de la ligne du formulaire (div).
	 * @param string $value - La valeur à mettre dans l'attribut "value" du champ de formulaire. Défaut: "".
	 * @return string
	 * @note On doit appliquer la fonction htmlspecialchars à la valeur.
	 * @note Ne pas oublier que la valeur va dans le CONTENU de la balise textarea.
	 */
	public function html_champDescription() {
		return '<div><label for="description">Description</label><div><textarea name="description" id="description" cols="30" rows="3">'.htmlspecialchars($this->description).'</textarea></div></div>';
	}

	/** html_champFichier
	 * Le champ "fichier". Retourne le HTML du champ caché (input) du formulaire.
	 * @param string $value - La valeur à mettre dans l'attribut "value" du champ de formulaire.
	 * @return string - Une balise input
	 * @note On doit appliquer la fonction htmlspecialchars à la valeur.
	 */
	public function html_champIdLac() {
		return '<input type="hidden" name="idLac" id="idLac" value="'.htmlspecialchars($this->galerie->idLac).'" />';
	}

	/** html_champFichier
	 * Le champ "fichier". Retourne le HTML du champ caché (input) du formulaire.
	 * @param string $value - La valeur à mettre dans l'attribut "value" du champ de formulaire.
	 * @return string - Une balise input
	 * @note On doit appliquer la fonction htmlspecialchars à la valeur.
	 */
	public function html_champId() {
		return '<input type="hidden" name="id" id="id" value="'.htmlspecialchars($this->id).'" />';
	}

	/** html_champDateAjout
	 * Le champ "fichier". Retourne le HTML du champ caché (input) du formulaire.
	 * @param integer $timestamp - Le timestamp de la date à régler dans le formulaire.
	 * @return string - Le div de la ligne de formulaire correspondante
	 * @uses $nomsMois - Un tableau des noms de mois en français. 1=>"Janvier" et 12=>"Décembre"
	 * @note Utiliser la fonction date() pour connaître les jour, mois et année
	 *   correspondant au timestamp
	 */
	static public function html_champDateAjout($timestamp) {
		$nomsMois = static::$nomsMois;
		// On trouve les jour, mois et année correspondant au timestamp
		$dateAjout_jour = date("d", $timestamp);
		$dateAjout_mois = date("m", $timestamp);
		$dateAjout_an = date("Y", $timestamp);
		// On compose le html
		$resultat = '';
		$resultat .= '<div>';
		$resultat .= '<label>Date d\'ajout</label>';
		$resultat .= '<div>';
		$resultat .= '<select name="dateAjout_jour">';
		for ($jour=1; $jour<=31; $jour++) {
				if ($jour == $dateAjout_jour) $resultat .= '<option value="'.$jour.'" selected="selected">'.$jour.'</option>';
				else $resultat .= '<option value="'.$jour.'">'.$jour.'</option>';
		}
		$resultat .= '</select>';
		$resultat .= '<select name="dateAjout_mois">';
		foreach ($nomsMois as $no=>$nom) {
				if ($no == $dateAjout_mois) $resultat .= '<option value="'.$no.'" selected="selected">'.$nom.'</option>';
				else $resultat .= '<option value="'.$no.'">'.$nom.'</option>';
		}
		$resultat .= '</select>';
		$resultat .= '<input style="width:auto;" type="text" maxlength="4" name="dateAjout_an" value="'.$dateAjout_an.'" />';
		$resultat .= '</div>';
		$resultat .= '</div>';
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

}
