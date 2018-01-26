<?php

class Add_accessController extends MY_Controller {
	public $libraries = ['console'];

	public function indexCliAction() {
		ci('console')->out('start\n');

		$this->globr(ROOTPATH,'Controller.php');

		ci('console')->out('end\n');
	}

	public function globr($searchDirectory,$searchPattern) {
		foreach (glob(escapeshellcmd($searchDirectory).'/*') as $folderitem) {
			if (is_dir($folderitem)) {
				$this->globr($folderitem,$searchPattern);
			} elseif (substr($folderitem,-strlen($searchPattern)) == $searchPattern) {
				$this->processone($folderitem);
			}
		}
	}

	public function processone($path) {
		/* is it a Orange / CI Controller? */
		if (strpos(file_get_contents($path),'MY_Controller') === false) {
			/* no */
			return;
		}

		ci('console')->out('<cyan>'.$path.'\n');

		$new_class_file = $this->make_dummy_class($path);

		$pos = strpos($path,'/controllers/');
		$path = substr($path,$pos + strlen('/controllers/'));

		$pathinfo = pathinfo($path);

		$directory = ($pathinfo['dirname'] == '.') ? '' : trim($pathinfo['dirname'],'/').'/';
		$original_class_name = substr($pathinfo['filename'],0,-10);

		/* now we can reflect */
		include $new_class_file;

		$new_class_name = basename($new_class_file,'.php');

		$class = new ReflectionClass($new_class_name);
		$methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

		foreach ($methods as $idx=>$ref_method) {
			$method = $ref_method->name;

			if (substr($method,-6) == 'Action') {
				//echo $method.chr(10);

				if (substr($method,-12) == 'DeleteAction') {
					$request_method = 'delete';
					$method = substr($method,0,-12);

					$this->add_access($directory,$original_class_name,$method,$request_method);
				} elseif(substr($method,-11) == 'PatchAction') {
					$request_method = 'patch';
					$method = substr($method,0,-11);

					$this->add_access($directory,$original_class_name,$method,$request_method);
				} elseif(substr($method,-10) == 'PostAction') {
					$request_method = 'post';
					$method = substr($method,0,-10);

					$this->add_access($directory,$original_class_name,$method,$request_method);
				} elseif(substr($method,-9) == 'CliAction') {
					/* don't insert */
				} elseif(substr($method,-6) == 'Action') {
					$request_method = 'get';
					$method = substr($method,0,-6);

					$this->add_access($directory,$original_class_name,$method,$request_method);
				}
			}
		}
	}

	public function add_access($dir,$class,$method,$request_method) {
		ci('console')->out('<yellow>'.trim($dir.' <red>'.$class.' <yellow>'.$method.' <green>'.$request_method).'\n');

		$key = 'url::/'.strtolower($dir.$class.'::'.$method.'~'.$request_method);
		$group = filter_human($class);
		$description = filter_human($dir.' '.$request_method.' '.$class.' '.$method);

		ci('o_permission_model')->add($key,$group,$description);
	}

	public function make_dummy_class($path) {
		$new_class_name = 'Controller_'.md5($path);
		$new_class_filepath = '/tmp/'.$new_class_name.'.php';

		file_put_contents($new_class_filepath,str_replace('class '.basename($path,'.php').' extends','class '.$new_class_name.' extends',file_get_contents($path)));

		return $new_class_filepath;
	}

} /* end class */
