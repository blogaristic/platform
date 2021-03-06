OroAsseticBundle
========================

To implement hashtag navigation all basic javascript and css files should be loaded in main template.
Each bundle can have a config file assets.yml with the list of js and css files.

```yaml
js:
    'some_group'
        - 'Assets/Path/To/Js/first.js'
        - 'Assets/Path/To/Js/second.js'
        - 'Assets/Path/To/Js/third.js'
css:
    'css_group':
        - 'Assets/Path/To/Css/first.css'
        - 'Assets/Path/To/Css/second.css'
        - 'Assets/Path/To/Css/third.css'
```

Js and css sections contain groups of files. This groups can be uncompressed for debugging.

The path to file can be in @BundleName/Resources/puclic/path/to/file.ext or bundles/bundle/path/to/file.ext. If the file path
contains @, then in uncompiled mode it will be taken via controller. If path doesn't contain @, then file will be taken
via request to web folder.

For example, to turn off compression of css files from 'css_group' the next configuration mut be added in config.yml file:

```yaml
oro_assetic:
    uncompress_js: ~
    uncompress_css: [css_group]
```

and cache must be cleaned.

To get list of all available assets groups next command should be used:

```php
php app/console oro:assetic:dump show-groups
```

The next code must be added in main template:

```
    {% oro_js filter='array with filters' output='js/name_of_output_file.js' %}
        <script type="text/javascript" src="{{ asset_url }}"></script>
    {% endoro_js %}
    {% oro_css filter='array with filters' output='css/name_of_output_file.css' %}
        <link rel="stylesheet" media="all" href="{{ asset_url }}" />
    {% endoro_css %}
```
These tags are the same as assettics "javascripts" and "stylesheet" tags but without list of files.

To compile blocks of files into single file, there is command

```
php app/console oro:assetic:dump
```

To use uncompressed files, in routing.yml must be added the next route rule:

```yml
oro_assets:
    resource: .
    type: oro_assetic
```