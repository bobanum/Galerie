<?php
namespace Galerie;
class Format {
	const FIT_INSIDE = "inside";
	const FIT_OUTSIDE = "outside";
	const FIT_FILL = "fill";
	const SCALE_DOWN = "down";
	const SCALE_UP = "up";
	const SCALE_ANY = "any";

	/** @var integer - La largeur de l'image resultante */
	public $width;
	/** @var integer - La largeur de l'image resultante */
	public $height;
	/** @var integer - Le type de fit de l'image */
	public $fit;
	/** @var string - Le scale de l'image */
	public $scale;
	/** @var string - Le chemin vers le watermark */
	public $watermark;
	/** @var string - L'opacité du watermark */
	public $watermark_opacity;
	/** @var string - La position X du watermark */
	public $watermark_posX;
	/** @var string - La position Y du watermark */
	public $watermark_posY;


	public function __construct($width, $height, $watermark=null, $fit=self::FIT_INSIDE, $scale="down") {
		$this->width = $width;
		$this->height = $height;
		$this->watermark = $watermark;
		$this->fit = $fit;
		$this->scale = $scale;
		$this->watermark_opacity = 60;
		$this->watermark_posX = "right - 10";
		$this->watermark_posY = "bottom - 10";

	}

	public function traiter($source, $destination) {
    if (is_string($source)) {
			$image = WideImage::load($source);
		} else {
			$image = $source;
		}

		if ($this->width != null || $this->height != null) $image = $image->resize($this->width, $this->height, $this->fit, $this->scale);

		if ($this->watermark) {
			$watermark = WideImage::load($this->watermark);
			$image = $image->merge($watermark, $this->watermark_posX, $this->watermark_posY, $this->watermark_opacity);
		}
		$image->saveToFile($destination);
	}
}
