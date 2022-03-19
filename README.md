# PHDB

Simple PHP Debugger written in PHP

## Requirement

PHP: >= 8.0

## Install

```bash
$ composer global require tzmfreedom/phdb
```

## Usage

```bash
$ phdb
# phdb -p {port} -c {config}
```

## Command

|command (alias)|description|
|---|---|
|next (n)|step over|
|step (s)|step into|
|finish (f)|step out|
|continue (c)|continue running script|
|break (b)|set breakpoint|
|info break|list breakpoints|
|delete|delete breakpoint|
|local (l)|show local variables|
|super_global (sg)|show super globals|
|constants (cn)|show constants|
|backtrace (bt)|show backtrace|
|source|show current source code|

### Examples

break on start calling function
```
break call {function_name}

ex) break call base64_encode
ex) break call App\Command\Hoge::handle
```

break on return calling function
```
break return {function_name}
```

break on exception
```
break exception {exception_name}
```

break at line no
```
break {file}:{line_no}

ex) break /path/to/file:12
```
