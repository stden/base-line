# Что это #
Роутер - класс, который позволяет управлять правилами по которым будут формироваться урлы сайта. Также роуте должен уметь распарсивать урл и выделять из него название контроллера, действия и параметры для действия.

# Что уже есть #
У меня вся функциональность этого класса находится в классе request.
Собственно правила описываются в ini конфиге.

Пример конфига
```
[default_project_route_with_params]
mask = "project/%projectId/:controller/:action"

[default_project_route]
mask = "project/%projectId/:controller/:action/:params"

[_empty_route]
mask="/"
controller = "index"
action = "index"

[default_route]
mask = ":controller/:action"

[default_route_with_params]
mask = ":controller/:action:/:params"
```
Всё компоненты, которые начинаются


Первая функция парсит урл, вторая строит регекспы из правил конфига.


```

	public function parse()
	{
		$needle=(isset($this->data['get']['route']) ? trim($this->data['get']['route']) : '');
		if($needle=='' || $needle=='/')
		{
			$currentRoute=$this->routes['_empty'];

		}
		else
		{
			$needle = trim($needle, '/\\');
			foreach ($this->routes as $route)
			{
				preg_match($route['regexp'],$needle,$vars);
				if(isset($vars[0]) && @$vars[0]==$needle)
				{
					$currentRoute=$route;
					for($i=1;$i<sizeof($vars);$i++)
					{
						$args[$route['matchVars'][$i]]=$vars[$i];
					}
					break;
				}
			}
		}
		if(!isset($args['controller']) && !isset($currentRoute['controller']))
		{
			$this->_log('errors', '[Request] Could not handle route '.$this->data['get']['route']);
		}
		else
			$this->controller=(isset($currentRoute['controller']) ? $currentRoute['controller'] : $args['controller']);
		$this->action=(isset($currentRoute['action']) ? $currentRoute['action'] : (isset($args['action']) ?$args['action'] : 'index'));
		if(isset($args))
		{
			unset($args['controller']);
			unset($args['action']);
			$this->args=$args;
			foreach($currentRoute as $var=>$value)
				if(!in_array($var,array('mask','regexp','action','controller','matchVars')))
					$this->args[$var]=$value;
		}				
	}

	private function _buildRegexps()
	{
		if(empty($this->routes)) die('No routes defined!');

		foreach($this->routes as $name=>$routeData)
		{
			if(!isset($routeData['regexp']) && isset($routeData['mask']))
				if(trim($routeData['mask'])!=='/')
				{
					$parts=explode('/',$routeData['mask']);

					$i=1;
					$regexp='/^';
					$vars=array();

					foreach ($parts as $part)
					{
						$regexp.=($part==$parts[0] ? '' : '\/');
						if($part[0]==':')
						{
							if(strlen($part)==1) $regexp.='[^\/]+';
							else
							{
								$vars[$i++]=str_replace(':','',$part);
								$regexp.='([^\/]+)';
							}
						}
						elseif ($part[0]=='%')
						{
							if(strlen($part)==1) $regexp.='[0-9]+';
							else
							{
								$vars[$i++]=str_replace('%','',$part);
								$regexp.='([0-9]+)';
							}
						}
						else $regexp.=$part;
					}
					$regexp.='$/';
					$this->routes[$name]['regexp']=$regexp;
					$this->routes[$name]['matchVars']=$vars;
				}
				else
				{
					$this->routes['_empty']=$this->routes[$name];
					$this->routes['_empty']['regexp']='/\//';
					unset($this->routes[$name]);
				}
		}
	}

```