# En

##	About

This tools for more comfortable work with Symfony.

### Services

#### FileUploaderService

FileUploader Service for most simple work with files upload  in Symfony.

Containts methods for configure FileType input with FormBuilderInterface (set constraints,
set allowed for upload mime-types, set messages).

Provided `upload` method.

Support LiipImagineBundle filters for resize, scaling upoloaded image file.

Tested in the Symfony 3.4 project.

##### Example settings

Put FileUploaderService object into form options in your controller.

Use it in FormType class for simple configure file upload form input.

###### Controller

```php
	use Landlib\SymfonyToolsBundle\Service\FileUploaderService;
	//...
	public function addAdvertAction(Request, $oRequest, FileUploaderService $oFileUploaderService)
	{
		$this->_oForm = $oForm = $this->createForm(get_class(new AdvertForm()), $this->_oAdvert, [
			'file_uploader' => $oFileUploaderService,
			'request' => $oRequest,
			//$this->_subdir - relative path to target files folder, for example 'images'
			'uploaddir' => $this->_subdir
		]);
	}
```

###### FormType.buildForm

Use FileUploaderService object in FormType class for simple configure file upload form input.

```php
public function buildForm(FormBuilderInterface $oBuilder, array $aOptions)
{
	//...

	// Get FileUploaderService
	$this->_oFileUploader = $options['file_uploader'];
	$this->_oRequest = $options['request'];

	//Set allowed for upload mime Types
	$this->_oFileUploader->addAllowMimetype('image/jpeg');
	$this->_oFileUploader->addAllowMimetype('image/png');
	$this->_oFileUploader->addAllowMimetype('image/gif');
	//Set file input label
	$this->_oFileUploader->setFileInputLabel('Append file!');
	//Set message, which will display if file type was not allowed
	$this->_oFileUploader->setMimeWarningMessage('Choose allowed file type');
	//Set maximum allowed image height
	$this->_oFileUploader->setMaxImageHeight(480);
	//Set maximum allowed image width
	$this->_oFileUploader->setMaxImageWidth(640);

	//You can use LiipImagineBundle for resize your files after upload
	//See https://symfony.com/doc/2.0/bundles/LiipImagineBundle/filters/sizing.html#relative-resize for create 'max_width' filter ('my_widen_filter' in Liip documentation).

	$this->_oFileUploader->addLiipBundleFilter('max_width');


	//Set directory for upload files
	$subdir = $options['uploaddir'];
	$sTargetDirectory = $this->_oRequest->server->get('DOCUMENT_ROOT') . '/' . $subdir;
	$this->_oFileUploader->setTargetDirectory($sTargetDirectory);

	//Get options array
	$aOptions = $this->_oFileUploader->getFileTypeOptions();
	$aOptions['translation_domain'] = 'Adform';

	//And add FileInput on your form
	$oBuilder->add('imagefile', FileType::class, $aOptions);

}
```

##### Example save

###### Controller

Call `upload` method in your controller.

```php
//...
	public function addAdvertAction(Request, $oRequest, FileUploaderService $oFileUploaderService)
	{
		//Create form
		$this->_oForm = $oForm = $this->createForm(get_class(new AdvertForm()), $this->_oAdvert, [
			'file_uploader' => $oFileUploaderService,
			'request' => $oRequest,
			//$this->_subdir - relative path to target files folder, for example 'images'
			'uploaddir' => $this->_subdir
		]);

		//Processing form data
		if ($this->_oForm->isValid()) {
			//save file
			$oFile = $this->_oForm['imagefile']->getData();
			if ($oFile) {
				//$sFileName will containts short file name (for example '1dsfd.jpeg')
				$sFileName = $this->_oFileUploaderService->upload($oFile);
				//For example App\Entity\Advert.imageLink must containts  attribute src value of image tag
				$this->_oAdvert->setImageLink('/' . $this->_subdir . '/' . $sFileName);
			}

			// ...
		}

	}
```

##### Example LiipImagineBundle configuration

It work in Symfony 3.4 project, created with Symfony CLI 4.7.3

```yaml
# file src/config/packages/liip_imagine.yaml 

liip_imagine:
    resolvers:
        default:
            web_path:
                web_root: "%kernel.project_dir%/public"
                cache_prefix: "images/cache"
    loaders:
        default:
            filesystem:
                data_root: "%kernel.project_dir%/public/"

    driver:               "gd"
    cache:                default
    data_loader:          default
    default_image:        null
    controller:
        filter_action:         liip_imagine.controller:filterAction
        filter_runtime_action: liip_imagine.controller:filterRuntimeAction

    filter_sets:
		cache: ~

		# name our second filter set "my_widen_filter"
        max_width:
            quality: 75
            filters:
                # use and setup the "relative_resize" filter
                relative_resize:
                    # given 50x40px, output 32x26px using "widen" option
                    widen: 240

		
		# the name of the "filter set"
        my_thumb:
            # adjust the image quality to 75%
            quality: 75
            # list of transformations to apply (the "filters")
            filters:

                # create a thumbnail: set size to 240x150 and use the "outbound" mode
                # to crop the image when the size ratio of the input differs
                thumbnail: { size: [240, 150], mode: outbound }

                # create a 2px black border: center the thumbnail on a black background
                # 4px larger to create a 2px border around the final image
                background: { size: [244, 154], position: center, color: '#000000' }
        
        
```

```yaml
# file src/config/routes/liip_routes.yaml 
liip_imagine_filter_runtime:
    path: /media/cache/resolve/{filter}/rc/{hash}/{path}
    defaults:
        _controller: '%liip_imagine.controller.filter_runtime_action%'
    methods:
        - GET
    requirements:
        filter: '[A-z0-9_-]*'
        path: .+

liip_imagine_filter:
    path: /media/cache/resolve/{filter}/{path}
    defaults:
        _controller: '%liip_imagine.controller.filter_action%'
    methods:
        - GET
    requirements:
        filter: '[A-z0-9_-]*'
        path: .+
```




# Ru

##	Что это

Это набор инструментов для более удобной работы с Symfony.

### Сервисы (Services)

#### FileUploaderService

FileUploader Service нужен для максимально простой работы с загрузкой файлов в Symfony.

Содержит методы для удобной настройки поля ввода FileType в FormBuilderInterface (установить ограничения,
установить разрешенные для загрузки MIME-типы, установить отображаемые сообщения).

Предоставляет удобный метод `upload` для сохранения загруженного файла.

Поддержка фильтров LiipImagineBundle для изменения размера, масштабирования загруженного файла изображения.

Проверен в проекте Symfony 3.4.

##### Example settings

Передайте  объект FileUploaderService в параметры формы в вашем контроллере.

Используйте его в классе FormType для простой настройки ввода формы загрузки файла.

###### Controller

```php
	use Landlib\SymfonyToolsBundle\Service\FileUploaderService;
	//...
	public function addAdvertAction(Request, $oRequest, FileUploaderService $oFileUploaderService)
	{
		$this->_oForm = $oForm = $this->createForm(get_class(new AdvertForm()), $this->_oAdvert, [
			'file_uploader' => $oFileUploaderService,
			'request' => $oRequest,
			//$this->_subdir - относительный путь к папке с сохраняемыми файлами, например 'images'
			'uploaddir' => $this->_subdir
		]);
	}
```

###### FormType.buildForm

Используйте экземпляр класса FileUploaderService в вашем классе формы для более удобной конфигурации инпута загрузки файлов

```php
public function buildForm(FormBuilderInterface $oBuilder, array $aOptions)
{
	//...

	// Получить экземпляр FileUploaderService
	$this->_oFileUploader = $options['file_uploader'];
	$this->_oRequest = $options['request'];

	//Установить типы файлов, допустимые для загрузки
	$this->_oFileUploader->addAllowMimetype('image/jpeg');
	$this->_oFileUploader->addAllowMimetype('image/png');
	$this->_oFileUploader->addAllowMimetype('image/gif');
	//Установить метку инпута выбора файлов
	$this->_oFileUploader->setFileInputLabel('Choose file');
	//Установить собщение, которое показывается при загрузке файла недопустимого типа
	$this->_oFileUploader->setMimeWarningMessage('Choose allowed file type');
	//Установить максимально допустимый размер для изображений
	$this->_oFileUploader->setMaxImageHeight(480);
	$this->_oFileUploader->setMaxImageWidth(640);

	//Вы можете использовать LiipImagineBundle для изменения размера ваших файлов после загрузки
	//Смотрите https://symfony.com/doc/2.0/bundles/LiipImagineBundle/filters/sizing.html#relative-resize 
	// для создания фильтра 'max_width' ('my_widen_filter' в документации Liip).

	$this->_oFileUploader->addLiipBundleFilter('max_width');


	//Установите каталог для хранения загруженных файлов
	$subdir = $options['uploaddir'];
	$sTargetDirectory = $this->_oRequest->server->get('DOCUMENT_ROOT') . '/' . $subdir;
	$this->_oFileUploader->setTargetDirectory($sTargetDirectory);

	//Получите массив конфигурации
	$aOptions = $this->_oFileUploader->getFileTypeOptions();
	//Можете добавить в него дополнительные опции
	$aOptions['translation_domain'] = 'Adform';

	//И добавьте FileInput на вашу форму
	$oBuilder->add('imagefile', FileType::class, $aOptions);

}
```

##### Example save

###### Controller

Вызовите метод `upload` в вашем контроллере.

```php
//...
	public function addAdvertAction(Request, $oRequest, FileUploaderService $oFileUploaderService)
	{
		//Создание формы
		$this->_oForm = $oForm = $this->createForm(get_class(new AdvertForm()), $this->_oAdvert, [
			'file_uploader' => $oFileUploaderService,
			'request' => $oRequest,
			//$this->_subdir - относительный путь к каталогу с файлами, например 'images'
			'uploaddir' => $this->_subdir
		]);

		//Processing form data
		if ($this->_oForm->isValid()) {
			//save file
			$oFile = $this->_oForm['imagefile']->getData();
			if ($oFile) {
				//$sFileName will containts short file name (for example '1dsfd.jpeg')
				$sFileName = $this->_oFileUploaderService->upload($oFile);
				//For example App\Entity\Advert.imageLink must containts  attribute src value of image tag
				$this->_oAdvert->setImageLink('/' . $this->_subdir . '/' . $sFileName);
			}

			// ...
		}

	}
```

##### Пример конфигурации LiipImagineBundle

Это работает в проекте Symfony 3.4, созданом с помощью консоли Symfony CLI 4.7.3

```yaml
# file config/packages/liip_imagine.yaml 

liip_imagine:
    resolvers:
        default:
            web_path:
                web_root: "%kernel.project_dir%/public"
                cache_prefix: "images/cache"
    loaders:
        default:
            filesystem:
                data_root: "%kernel.project_dir%/public/"

    driver:               "gd"
    cache:                default
    data_loader:          default
    default_image:        null
    controller:
        filter_action:         liip_imagine.controller:filterAction
        filter_runtime_action: liip_imagine.controller:filterRuntimeAction

    filter_sets:
		cache: ~

		# name our second filter set "my_widen_filter"
        max_width:
            quality: 75
            filters:
                # use and setup the "relative_resize" filter
                relative_resize:
                    # given 50x40px, output 32x26px using "widen" option
                    widen: 240

		
		# the name of the "filter set"
        my_thumb:
            # adjust the image quality to 75%
            quality: 75
            # list of transformations to apply (the "filters")
            filters:

                # create a thumbnail: set size to 240x150 and use the "outbound" mode
                # to crop the image when the size ratio of the input differs
                thumbnail: { size: [240, 150], mode: outbound }

                # create a 2px black border: center the thumbnail on a black background
                # 4px larger to create a 2px border around the final image
                background: { size: [244, 154], position: center, color: '#000000' }
        
        
```

```yaml
# file config/routes/liip_routes.yaml 
liip_imagine_filter_runtime:
    path: /media/cache/resolve/{filter}/rc/{hash}/{path}
    defaults:
        _controller: '%liip_imagine.controller.filter_runtime_action%'
    methods:
        - GET
    requirements:
        filter: '[A-z0-9_-]*'
        path: .+

liip_imagine_filter:
    path: /media/cache/resolve/{filter}/{path}
    defaults:
        _controller: '%liip_imagine.controller.filter_action%'
    methods:
        - GET
    requirements:
        filter: '[A-z0-9_-]*'
        path: .+
```