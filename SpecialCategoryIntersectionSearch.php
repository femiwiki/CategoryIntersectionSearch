<?php

class SpecialCategoryIntersectionSearch extends SpecialPage {
	private $categories = [];
	private $exCategories = [];

	public function __construct() {
		parent::__construct( 'CategoryIntersectionSearch' );
	}

	/**
	 * @param string $par
	 */
	public function execute( $par ) {
		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();

		if ( $par == '' ) {
			$output->addWikitext( $this->msg( 'categoryintersectionsearch-noinput' ) );
			return;
		}
		$titleParam = str_replace( '_', ' ', $par );
		$this->splitPar( $titleParam );

		if ( count( $this->categories ) === 0 && count( $this->exCategories ) > 0 ) {
			$output->addWikitext( $this->msg( 'categoryintersectionsearch-noinput' ) );
			return;
		} elseif ( count( $this->categories ) < 2 && count( $this->exCategories ) == 0 ) {
			$output->redirect( Title::newFromText( 'Category:' . $titleParam )->getFullURL() );
			return;
		}

		$title = implode( '", "', $this->categories );
		if ( count( $this->exCategories ) !== 0 ) {
			$title .= ', -"' . implode( '", -"', $this->exCategories );
		}

		$output->setPageTitle( $this->msg( 'categoryintersectionsearch-page-title', $title ) );

		// 여기서 아래는 mediawiki 1.27.1의 CategoryViewer.php과 동일
		$oldFrom = $request->getVal( 'from' );
		$oldUntil = $request->getVal( 'until' );

		$reqArray = $request->getValues();
		$from = $until = [];
		foreach ( [ 'page', 'subcat', 'file' ] as $type ) {
			$from[$type] = $request->getVal( "{$type}from", $oldFrom );
			$until[$type] = $request->getVal( "{$type}until", $oldUntil );

			// Do not want old-style from/until propagating in nav links.
			if ( !isset( $reqArray["{$type}from"] ) && isset( $reqArray["from"] ) ) {
				$reqArray["{$type}from"] = $reqArray["from"];
			}
			if ( !isset( $reqArray["{$type}to"] ) && isset( $reqArray["to"] ) ) {
				$reqArray["{$type}to"] = $reqArray["to"];
			}
		}
		unset( $reqArray["from"] );
		unset( $reqArray["to"] );
		// 위에서 여기까지는 mediawiki 1.27.1의 CategoryViewer.php과 동일

		$viewer = new CategoryIntersectionSearchViewer(
			Title::newFromText( $title ),
			$this->getContext(),
			$from,
			$until,
			$reqArray,
			$this->categories,
			$this->exCategories
		);
		$output->addHTML( $viewer->getHTML() );
	}

	/**
	 * @param string $par
	 */
	private function splitPar( $par ) {
		$par = explode( ",", $par );
		$count = count( $par );
		if ( $count === 1 ) {
			return null;
		}

		for ( $i = 0; $i < $count; $i++ ) {
			if ( strpos( $par[$i], "/" ) === false ) {
				return null;
			}
			$par[$i] = trim( $par[$i] );
			$pos = strrchr( $par[$i], ":" );
			if ( $pos !== false ) {
				$par[$i] = trim( substr( $pos, 1 ) );
			}
			if ( substr( $par[$i], 0, 1 ) !== '-' ) {
				$this->categories[] = $par[$i];
			} else {
				$this->exCategories[] = substr( $par[$i], 1 );
			}
		}
	}

	/**
	 * @return string
	 */
	protected function getGroupName() {
		return 'pages';
	}
}
