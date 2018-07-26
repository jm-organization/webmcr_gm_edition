<?php
/**
 * Copyright (c) 2018.
 * MagicMCR является отдельным и независимым продуктом.
 * Исходный код распространяется под лицензией GNU General Public License v3.0.
 *
 * MagicMCR не является копией оригинального движка WebMCR, а лишь его подверсией.
 * Разработка MagicMCR производится исключительно в частных интересах. Разработчики, а также лица,
 * участвующие в разработке и поддержке, не несут ответственности за проблемы, возникшие с движком.
 */

/**
 * Created in JM Organization.
 *
 * @e-mail       : admin@jm-org.net
 * @Author       : Magicmen
 *
 * @Date         : 21.07.2018
 * @Time         : 12:42
 *
 * @Documentation:
 */

namespace mcr\exception;


use mcr\log;

class exception_handler
{
    /**
     * @var array
     */
    public $configs = [];

    /**
     * Содержит экземпляр исключения
     *
     * @var \Exception
     */
    public $exception;

    /**
     * Сообщение, которое было выброшено исключением
     *
     * @var
     */
    public $message;

    /**
     * Код исключения
     *
     * @var
     */
    public $code;

    /**
     * Файл в котором было выброшено исключение
     *
     * @var
     */
    public $file;

    /**
     * Строка на которой было выброшено исключение
     *
     * @var
     */
    public $line;

    /**
     * Стек вызова функций до выброса исключения
     *
     * @var
     */
    public $trace;

    /**
     * Стек вызова, представленный строкой
     *
     * @var
     */
    public $trace_as_string;

    /**
     * Предидущее исключение
     *
     * @var
     */
    public $previous;

    /**
     * exception_handler constructor.
     *
     * @param \Exception $exception
     * @param array      $configs
     */
    public function __construct(\Exception $exception, array $configs = [])
	{
		$this->set_exception($exception);

		$configs += [
			'log' => true,
			'throw_on_screen' => true,
		];

		$this->configs = $configs;
	}

    /**
     * Заполняет обработчик исключений данными исключения
     *
     * @param \Exception $exception
     */
    public function set_exception(\Exception $exception)
	{
		$this->exception = $exception;

		$this->message = $exception->getMessage();
		$this->code = $exception->getCode();
		$this->file = $exception->getFile();
		$this->line = $exception->getLine();
		$this->trace = $exception->getTrace();
		$this->trace_as_string = $exception->getTraceAsString();
		$this->previous = $exception->getPrevious();
	}

    /**
     * Обрабатывает исключение.
     *
	 * Если в конфигах указно выводить исключения в логи -
	 * будет совершена запись в лог файл.
     *
     * @return $this
     */
    public function handle()
	{
		$configs = $this->configs;
		global $log;

		if ($configs['log']) {
			$this->write_log($log);
		}

		return $this;
	}

	private function write_log(log $logger)
	{
		$message = $this->message . ' File: ' . $this->file . ' on line: ' . $this->line . "\nStack trace:\n" . $this->trace_as_string;

		$logger->write($message, get_class($this->exception));
	}

    /**
     * Выводит сообщение об ошибке на экран.
     */
    public function throw_on_screen()
	{
		ob_start();

		?><!DOCTYPE HTML>
		<html>
		<head>
			<meta charset="utf-8">
			<title>500 Internal Server Error</title>

			<style>
				.errors {
					width: 928px;
					margin: 0 auto;
					padding-top: 10px;
					padding-bottom: 12px;
				}

				fieldset {
					margin-bottom: 20px;
					color: rgba(0, 0, 0, 0.58);
					font-family: Verdana, Arial, sans-serif;
					font-size: 14px;
                    max-width: 100%;
                    word-break: break-all;
				}

				legend { color: black; margin-bottom: 5px; }
				legend span { font-weight: bold; }

				h4 { margin: 0 0 7px; color: rgba(0, 0, 0, 0.66) }

				p { margin: 0; }
				p:not(:last-child) { margin-bottom: 20px; }

				hr { margin-bottom: 20px; }

				ol { margin: 0; padding-left: 18px }
				ol li {
					padding-left: 10px;
					margin-bottom: 4px;
					color: rgba(0, 0, 0, 0.8);
				}

				ol li > span,
				ol li > span > i,
				ol li > span > span {
					display: inline-table;
				}

				.function, .file,
				.function i, .file i {
					margin-right: 5px;
				}

				.class,
				.func,
				.filename {
					border-bottom: 1px dashed;
					cursor: pointer;
				}

				.arguments:before,
				.arguments:after {
					color: black;

				}
				.arguments:before { content: '('; }
				.arguments:after { content: ')'; }

				.var-type,
				.filename {
					color: rgba(0, 0, 0, 0.58);
				}
				.var-type { margin-right: 5px; }

                .detail:before {
                    content: attr(data-prefix);
                }
			</style>
            <script type="application/javascript">
                function toggle_class(_for, _class) {
                    _for.classList.toggle(_class);
                }
            </script>
		</head>
		<body>

			<div class="errors">

				<fieldset>Whoops, looks like something went wrong.</fieldset>

				<?php
                    // Если включен вывод исключений на экран, то выводим их.
					if ($this->configs['throw_on_screen']) $this->get_errors();
				?>

			</div>

		</body>
		</html><?php

        // Забираем содержимое из будефора, очишаем его.
        // Делаем ответ с сервера со статусом 500 (внутренняя ошибка сервера)
        // и содержимым, которое было получено из буфера вывода.
		response()->content(ob_get_clean(), 500);
	}

    /**
     * Выводит текущее исключение.
     *
	 * Если есть предидушие исключения,
	 * устанавливает его и вызывает себя рекурсивно.
     */
    private function get_errors()
	{
	    // определяем корневую директорию,
        // заменяя разделители на человеко-понятные
		$root_dir = str_replace('\\', '/', MCR_ROOT);

		// получаем короткое имя класса
		$namespace_class = explode('\\', get_class($this->exception));
		$short_class_name = end($namespace_class);

		// генерируем блок ошибки.
		?>
		<fieldset>
			<legend><span><?= $short_class_name ?></span></legend>
			<h4><?= $this->message ?></h4>
			<p>File: <span class="filename" title="<?= $this->file ?>"><?= str_replace($root_dir, '../', str_replace('\\', '/', $this->file)) ?></span>, on line: <?= $this->line ?></p>

			<hr>

			<?php $this->parse_trace($this->trace); ?>
		</fieldset>
		<?php

		if (!empty($this->previous)) {
		    // Перезаписываем текущее исключение.
			$this->set_exception($this->previous);

			$this->get_errors();
		}
	}

    /**
	 * Преобразует массив стека вызовов
	 * в строчный вид с разметкой HTML.
     *
     * @param array $trace
     */
    private function parse_trace(array $trace)
	{
		?>
		<ol><?php

			foreach ($trace as $item) {
			    // Получаем аргументы функции
				$arguments = isset($item['args']) ? $this->parse_arguments($item['args']) : [];
				?><li>

                    <?php if (isset($item['function'])) { ?>
                        <span class="function">
                            <i>at</i>
                            <?php if (isset($item['class'])) {
                                $namespace_class = explode('\\', $item['class']);
                                $short_class_name = end($namespace_class);
                                ?>
                                <span class="class" title="<?= $item['class'] ?>"><?= $short_class_name ?></span>
                                <span><?= $item['type'] ?></span>
                            <?php } ?>
                            <span class="func" title="<?= $item['function'] ?>()"><?= $item['function'] ?></span>
                            <span class="arguments"><?= $arguments ?></span>
                        </span>
                    <?php } ?>

					<span class="file">
						<i>in</i>
						<span class="filename" title="<?= $item['file'] ?>"><span onclick="toggle_class(this, 'detail')" data-prefix="<?= str_replace(basename($item['file']), '', $item['file']) ?>"><?= basename($item['file']) ?></span> line <?= $item['line'] ?></span>
					</span>

				</li><?php
			}

		?></ol>
		<?php
	}

	private function parse_arguments(array $args)
	{
		$arguments = [];

		foreach ($args as $arg) {

			switch (true) {
				case is_array($arg):
					$arguments[] = '<span class="var-type">array</span> ' . str_replace("\r\n", ' ', var_export($arg, true));
					break;
				case is_object($arg):
					$namespace_class = explode('\\', get_class($arg));
					$short_class_name = end($namespace_class);

					$arguments[] = '<span class="var-type">object</span> <span class="class" title="' . get_class($arg) . '">' . $short_class_name . '</span>';
					break;
				default:
					$arguments[] = '<span class="var-type">' . gettype($arg) . '</span> ' . var_export($arg, true);
					break;
			}

		}

		return implode(', ', $arguments);
	}
}