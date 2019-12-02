<?php
namespace Landlib\SymfonyToolsBundle\Service;

use \Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;
use Transliterator;

// TODO service -> MyToolBundle


/**
 * 
 * Usage 
 *
 * settings:
 * 
 *    controller:
 * 
 *		$this->_oForm = $oForm = $this->createForm(get_class(new AdvertForm()), $this->_oAdvert, [
			'file_uploader' => $oFileUploaderService,
			'request' => $oRequest,
			'uploaddir' => $this->_subdir
		]);
 *
 *	  in buildForm:
 *		
 *		$this->_oFileUploader = $options['file_uploader'];
		$this->_oRequest = $options['request'];
		$this->_oFileUploader->addAllowMimetype('image/jpeg');
		$this->_oFileUploader->addAllowMimetype('image/png');
		$this->_oFileUploader->addAllowMimetype('image/gif');
		$this->_oFileUploader->setFileInputLabel('Append file!');
		$this->_oFileUploader->setMimeWarningMessage('Choose allowed file type');
		$this->_oFileUploader->setMaxImageHeight(480);
		$this->_oFileUploader->setMaxImageWidth(640);
		$this->_oFileUploader->addLiipBundleFilter('my_thumb');
		$subdir = $options['uploaddir'];
		$sTargetDirectory = $this->_oRequest->server->get('DOCUMENT_ROOT') . '/' . $subdir;
		$this->_oFileUploader->setTargetDirectory($sTargetDirectory);
		$aOptions = $this->_oFileUploader->getFileTypeOptions();
		$aOptions['translation_domain'] = 'my_form_translations';
		$oBuilder->add('imagefile', FileType::class, $aOptions);
 *
 *
 * 
 * save:
 * 
 *     controller: 
 *
 *
 * if ($this->_oForm->isValid()) {
        //save file
		$oFile = $this->_oForm['imagefile']->getData();
        if ($oFile) {
            $sFileName = $this->_oFileUploaderService->upload($oFile);
            $this->_oAdvert->setImageLink('/' . $this->_subdir . '/' . $sFileName);
        }

        // ...
    }
***/

/**
 *  
*/
class FileUploaderService
{
	/** @property string $_sDefaultFileInputLabel */
	private $_sDefaultFileInputLabel = 'Add file';
	
	/** @property string $_sFileInputLabel */
	private $_sFileInputLabel;
	
	/** @property array $_aConstraints */
	private $_aConstraints = [];
	
	/** @property string $_sConstraintClassName */
	private $_sConstraintClassName = '\Symfony\Component\Validator\Constraints\File';
	
	/** @property string $_sError humanly error text */
	private  $_sError;
	
	/** @property string $_sErrorInfo extend info about error*/
	private $_sErrorInfo;
	
	/** @property array $_aLiipImageFilters @see addLiipBundleFilter */
	private $_aLiipImageFilters = [];
	
	/** @property string | null $translationDomain translation domain*/
	private $_sTranslationDomain = null;
	
	
	public function __construct(ContainerInterface $container)
	{
		$this->oContainer = $container;
		$this->translator = $container->get('translator');
	}
	/**
	 * Helper for create argument $options FormBuilderInterface::add('..', FileType::class, $options)
	 * Пoмощник для формирования аргумента $options метода FormBuilderInterface::add('..', FileType::class, $options)
	 * use setMaxSize, addAllowMimetype, setMimetypeMessage, setMaxWidth, setMaxHeight before call this method
	*/
	public function getFileTypeOptions() : array
	{
		$t = $this->translator;
		$this->_sFileInputLabel = is_null($this->_sFileInputLabel) ? $this->_sDefaultFileInputLabel : $this->_sFileInputLabel;
		$a = [
			'mapped'   => false,
			'required' => false,
			'label'    => $t->trans($this->_sFileInputLabel)
		];
		
		if ($this->_aConstraints) {
			$sConstraintsClassName = $this->_sConstraintClassName;
			$a['constraints'] = [new $sConstraintsClassName($this->_aConstraints)];
		}
		
		if ($this->_sTranslationDomain) {
			$a['translation_domain'] = $this->_sTranslationDomain;
		}
		
		return $a;
	}
	/**
	 * @param string mime, example 'application/pdf' or 'image/jpeg'
	**/
	public function addAllowMimetype(string $sMime)
	{
		if (strpos($sMime, 'image/') !== false) {
			$this->_setConstraintsTypeImage();
		}
		if (!isset($this->_aConstraints['mimeTypes'])) {
			$this->_aConstraints['mimeTypes'] = [];
		}
		$this->_aConstraints['mimeTypes'][] = $sMime;
	}
	/**
	 * @param int $nKBytes kilobytes
	**/
	public function setMaxFileSize(int $nKBytes)
	{
		$this->_aConstraints['maxSize'] = $nKBytes . 'k';
	}
	/**
	 * @param int $nWidth image width
	**/
	public function setMaxImageWidth(int $nWidth)
	{
		$this->_setConstraintsTypeImage();
		$this->_aConstraints['maxWidth'] = $nWidth;
	}
	/**
	 * @param int $nHeight image height
	**/
	public function setMaxImageHeight(int $nHeight)
	{
		$this->_setConstraintsTypeImage();
		$this->_aConstraints['maxHeight'] = $nHeight;
	}
	/**
	 * @param string sWarningMessage no translate message
	**/
	public function setMimeWarningMessage(string $s)
	{
		/** @var \Symfony\Component\Translation\DataCollectorTranslator $t */
		$t = $this->translator;
		$this->_aConstraints['mimeTypesMessage'] = $t->trans($s, [], $this->_sTranslationDomain);
	}
	/**
	 * @param string label no translate message
	**/
	public function setFileInputLabel(string $s)
	{
		/** @var \Symfony\Component\Translation\DataCollectorTranslator $t */
		$t = $this->translator;
		$this->_sFileInputLabel = $t->trans($s, [], $this->_sTranslationDomain);
	}
	
	public function setTargetDirectory(string $sTargetDirectory)
	{
		$this->_sTargetDirectory = $sTargetDirectory;
	}
	/**
	 * Upload action
	**/
	public function upload(\Symfony\Component\HttpFoundation\File\UploadedFile $file) : string
	{
		$originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
		$transliterator = \Transliterator::create('Any-Latin');
		$transliteratorToASCII = \Transliterator::create('Latin-ASCII');
		$safeFilename = $transliteratorToASCII->transliterate($transliterator->transliterate($originalFilename));

		$fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

		try {
			$sFolder = $this->getTargetDirectory();
			$file->move($sFolder, $fileName);
			foreach ($this->_aLiipImageFilters as $sFilterName) {
				$this->_applyFilter($sFolder . '/' . $fileName, $sFilterName);
			}
		} catch (FileException $e) {
			$t = $this->translator;
			$this->_sError = $t->trans('Unable upload file', [], $this->_sTranslationDomain);
			$this->_sErrorInfo = $e->getMessage();
			$fileName = '';
		}
		return $fileName;
    }
	/**
	 * It require use \Liip\ImagineBundle\LiipImagineBundle in your project
	 * @param string $sFilterName filter 
	**/
    public function addLiipBundleFilter(string $sFilterName)
	{
		$this->_aLiipImageFilters[] = $sFilterName;
	}
	/**
	 * 
	**/
    public function getTargetDirectory()
    {
        return $this->_sTargetDirectory;
    }
    public function getUploadError()
    {
        return $this->_sError;
    }
    public function getUploadExceptionMessage()
    {
        return $this->_sErrorInfo;
    }
    public function getUploadErrorInfo()
    {
        return $this->getUploadExceptionMessage();
    }
	/**
	 * @param string $sTranslationDomain
	**/
	public function setTranslationDomain(string $sTranslationDomain)
	{
		$this->_sTranslationDomain = $sTranslationDomain;
	}
	public function getTranslationDomain() : ?string
	{
		return $this->_sTranslationDomain;
	}
	/**
	 * Set Constraints type Image
	**/
	private function _setConstraintsTypeImage()
	{
		$this->_sConstraintClassName = str_replace('\File', '\Image', $this->_sConstraintClassName);
	}
	
	 /**
     * Apply LiipImagineBundle filter
     * 
     * @param string $path absolute path to image file
     * @param string $filter the Imagine filter to (use Bundle LiipImagineBundle);
     */
    private function _applyFilter($path, $filter)
	{
        $tpath = $path;													// absolute path of saved thumbnail
		$container = $this->oContainer;                                  // the DI container
		$oRequest = $container->get('request_stack')->getCurrentRequest();
		$sDr = $oRequest->server->get('DOCUMENT_ROOT');
		$path = str_replace($sDr, '', $tpath);
        
        $dataManager = $container->get('liip_imagine.data.manager');    // the data manager service
        /** @var \Liip\ImagineBundle\Imagine\Filter\FilterManager $filterManager  */
		$filterManager = $container->get('liip_imagine.filter.manager');// the filter manager service
		
        $image = $dataManager->find($filter, $path);                    // find the image and determine its type
		$response = $filterManager->applyFilter($image, $filter);
        //$response = $filterManager->get($this->getRequest(), $filter, $image, $path); // run the filter 
        $thumb = $response->getContent();                               // get the image from the response

        $f = fopen($tpath, 'w');                                        // create thumbnail file
        fwrite($f, $thumb);                                             // write the thumbnail
        fclose($f);                                                     // close the file
    }
}
