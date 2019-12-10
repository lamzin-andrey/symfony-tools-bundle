#### Content / Содержание

[Ru](#ru)

[En](#en)


# En

#### Content

[About](#about)

[Services](#services)

[FileUploaderService](#fileuploaderservice)

[Example settings](#example-settings)

[Example save](#example-save)

[Example liipimaginebundle configuration](#example-liipimaginebundle-configuration)

[Commands](#commands)

[landlib:decorate-controller](#landlibdecorate-controller)


##	About

## Install

`composer require landlib/symfonytoolsbundle`

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
public function buildForm(FormBuilderInterface $oBuilder, array $options)
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

### Commands

#### landlib:decorate-controller

[About](#about-1)

[Usage](#usage)

[Тroubles](#troubles)

##### About

When I decided to decorate the FOSUserBundle ResettingController controller, it turned out to be difficult to write a new service registration configuration. Besides the fact that I have to find the aliases of all the services that the original controller accepts, I have to write a class, almost all methods of which simply call the methods of the decorated controller (it's boring!).

We want to use good ready-made solutions as much as possible, which means that the operation of decorating the controller from a third-party bundle will be routine.

Because I created command:

```bash
php bin/console landlib:decorate-controller
```

which creates a "blank" for the controller class that decorates the desired service and displays in stdout the yaml fragment of the configuration of the new service. You just have to copy this configuration to your

`config/services.yaml`.

Generated controller already containts wrappers for all methods of original controller.

##### Install

`composer require landlib/symfonytoolsbundle`

##### Usage

For example decorate `FOS\UserBundle\Controller\ResettingController`

`php bin/console landlib:decorate-controller`

Command will request enter the path to override controller. Need enter absolute path, for example

`/home/user/sym3.4project/vendor/friendsofsymfony/user-bundle/Controller/ResettingController.php`

After request command wil generate file

`/home/user/sym3.4project/src/Controller/ResettingController.php`

**If file already exists, it will rewrite or remove! No copies are saved.**

и выведет фрагмент yaml конфигурации:

and will output the yaml fragment of the configuration:

```
Add in your configuration config/services.yaml: 
==================


    App\Controller\ResettingController:
        decorates: fos_user.resetting.controller
        arguments:
            - '@App\Controller\ResettingController.inner'
            - '@event_dispatcher'
            - '@fos_user.resetting.form.factory'
            - '@fos_user.user_manager'
            - '@fos_user.util.token_generator'
            - '@fos_user.mailer'
            - '%fos_user.resetting.retry_ttl%'
            - '@service_container'


==================


Remember to change the name of the controller in the routes or annotation file.
```

**Pay attention to the last line of output, the route (or routes) for those actions that you want to reload must be manually adjusted in the route configuration! (Because the team cannot know which of the controller actions you want to overload).**

##### Troubles

If during the process you suddenly saw the error `Cannot autowire service ...`, then you forgot to copy the configuration fragment to your services.yaml.

You can remove file

`/home/user/sym3.4project/src/Controller/ResettingController.php`

(file path from example [Usage](#usage) section)


or append yaml configuration fragment to your services.yaml.


# Ru

#### Содержание

[Что это](#что-это)

[Сервисы (Services)](#сервисы-services)

[FileUploaderService](#fileuploaderservice-1)

[Example settings](#example-settings-1)

[Example save](#example-save-1)

[Пример конфигурации LiipImagineBundle](#пример-конфигурации-liipimaginebundle)

[Commands](#commands-1)

[landlib:decorate-controller](#landlibdecorate-controller-1)


##	Что это

Это набор инструментов для более удобной работы с Symfony.

## Установка 

`composer require landlib/symfonytoolsbundle`

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
public function buildForm(FormBuilderInterface $oBuilder, array $options)
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

### Commands

#### landlib:decorate-controller

[Что это](#что-это-1)

[Использование](#использование)

[Проблемы](#проблемы)

##### Что это

Когда я решил декорировать контроллер FOSUserBundle ResettingController оказалось сложным написать конфигурацию регистрации нового сервиса. Помимо  того, что я должен найти псевдонимы всех сервисов, которые принимает оригинальный контроллер, я должен написать класс, почти все методы которого просто вызывают методы декорируемого контроллера (это скучно!).

Мы хотим как можно больше использовать хорошие готовые решения, это значит, что операция декорирования контроллера из стороннего пакета будет рутинной.

Поэтому я создал консольную команду

```bash
php bin/console landlib:decorate-controller
```

которая создаёт заготовку класса контроллера, декорирующего нужный сервис и выводит в stdout фрагмент yaml конфигурации нового сервиса. Вам остаётся просто скопировать эту конфигурацию в ваш 

`config/services.yaml`.

##### Установка

`composer require landlib/symfonytoolsbundle`

##### Использование

На примере декорирования `FOS\UserBundle\Controller\ResettingController`

`php bin/console landlib:decorate-controller`

Команда попросит ввести путь к перегружаемому контроллеру. Надо ввести абсолютный путь, например

`/home/user/sym3.4project/vendor/friendsofsymfony/user-bundle/Controller/ResettingController.php`

После этого команда сгенерирует файл 

`/home/user/sym3.4project/src/Controller/ResettingController.php`

**Если файл уже существует, он будет перезаписан или удалён! Копии не сохраняется.**

и выведет фрагмент yaml конфигурации:

```
Add in your configuration config/services.yaml: 
==================


    App\Controller\ResettingController:
        decorates: fos_user.resetting.controller
        arguments:
            - '@App\Controller\ResettingController.inner'
            - '@event_dispatcher'
            - '@fos_user.resetting.form.factory'
            - '@fos_user.user_manager'
            - '@fos_user.util.token_generator'
            - '@fos_user.mailer'
            - '%fos_user.resetting.retry_ttl%'
            - '@service_container'


==================


Remember to change the name of the controller in the routes or annotation file.
```

**Обратите внимание, на последнюю строку вывода, маршрут(или маршруты) для тех actions, которые вы хотите перегрузить необходимо корректировать в конфигурации маршрутов вручную! (Потому что команда не может знать, какие из actions контроллера вы хотите перегрузить).**

##### Проблемы

Если в процессе работы вы внезапно увидели ошибку `Cannot autowire service...`, значит вы забыли скопировать фрагмент конфигурации в ваш services.yaml.

Вы можете удалить файл 

`/home/user/sym3.4project/src/Controller/ResettingController.php`

(путь к файлу приведен для примера из раздела [Использование](#использование))

или добавить фрагмент конфигурации в ваш services.yaml.