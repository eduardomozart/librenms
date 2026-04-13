<?php

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

if (isset($_POST['config'])) {
    try {
        $oxidized_cfg = Yaml::parse($_POST['config']);
        $validate_cfg = validate_oxidized_cfg($oxidized_cfg);
        foreach ($validate_cfg as $error) {
            $error = htmlspecialchars((string) $error);
            echo "<div class='alert alert-danger'>$error</div>";
        }
        if (empty($validate_cfg)) {
            echo '<div class="alert alert-success">Config has validated ok</div>';
        }
    } catch (ParseException $e) {
        echo "<div class='alert alert-danger'>{$e->getMessage()}</div>";
    }
}
?>

    <style>
        .oxidized-editor-wrap {
            display: flex;
            font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
            font-size: 13px;
            line-height: 1.5;
            border: 1px solid #ccc;
            border-radius: 4px;
            overflow: hidden;
        }
        .oxidized-line-numbers {
            padding: 6px 8px;
            background: #f5f5f5;
            border-right: 1px solid #ccc;
            text-align: right;
            color: #999;
            user-select: none;
            overflow: hidden;
            white-space: pre;
            min-width: 2em;
        }
        .oxidized-editor-wrap textarea {
            flex: 1;
            border: none;
            border-radius: 0;
            box-shadow: none;
            font-family: inherit;
            font-size: inherit;
            line-height: inherit;
            padding: 6px 8px;
            resize: vertical;
            overflow-y: auto;
        }
        .oxidized-editor-wrap textarea:focus {
            outline: none;
            box-shadow: none;
        }
    </style>

    <form method="post">
        <?php echo csrf_field() ?>
        <div class="form-group">
            <label for="oxidized-config">Paste your Oxidized yaml config:</label>
            <div class="oxidized-editor-wrap">
                <div class="oxidized-line-numbers" id="oxidized-line-numbers" aria-hidden="true">1</div>
                <textarea id="oxidized-config" name="config" rows="20" class="form-control" placeholder="Paste your Oxidized yaml config"><?php echo htmlspecialchars((string) $_POST['config']); ?></textarea>
            </div>
        </div>
        <button type="submit" class="btn btn-default btn-primary">Validate YAML</button>
    </form>

    <script>
        (function () {
            var textarea = document.getElementById('oxidized-config');
            var gutter   = document.getElementById('oxidized-line-numbers');

            function updateLineNumbers() {
                var count = textarea.value.split('\n').length;
                var lines = '';
                for (var i = 1; i <= count; i++) {
                    lines += i + (i < count ? '\n' : '');
                }
                gutter.textContent = lines;
            }

            function syncScroll() {
                gutter.scrollTop = textarea.scrollTop;
            }

            textarea.addEventListener('input', updateLineNumbers);
            textarea.addEventListener('scroll', syncScroll);

            updateLineNumbers();
        })();
    </script>

<?php

function validate_oxidized_cfg($tree, $wanted_leaf = false)
{
    $valid_config = [
        'username' => 'string',
        'password' => 'string',
        'model' => 'string',
        'interval' => 'int',
        'use_syslog' => 'boolean',
        'log' => 'string',
        'debug' => 'boolean',
        'threads' => 'int',
        'timeout' => 'int',
        'retries' => 'int',
        'prompt' => 'string',
        'models' => 'array',
        'vars' => [
            'enable' => 'boolean',
            'ssh_no_exec' => 'boolean',
            'remove_secret' => 'boolean',
        ],
        'groups' => 'array',
        'rest' => 'string',
        'pid' => 'string',
        'input' => [
            'default' => 'string',
            'debug' => 'boolean',
            'ssh' => [
                'secure' => 'boolean',
            ],
        ],
        'output' => [
            'default' => 'string',
            'git' => [
                'user' => 'string',
                'email' => 'string',
                'repo' => 'string',
            ],
        ],
        'source' => [
            'default' => 'string',
            'csv' => [
                'file' => 'string',
                'delimiter' => 'string',
                'map' => [
                    'name' => 'string',
                    'model' => 'string',
                    'username' => 'string',
                    'password' => 'string',
                    'group' => 'group',
                ],
                'vars_map' => [
                    'enable' => 'string',
                ],
            ],
            'http' => [
                'url' => 'string',
                'scheme' => 'string',
                'secure' => 'boolean',
                'delimiter' => 'string',
                'user' => 'string',
                'pass' => 'string',
                'map' => [
                    'name' => 'string',
                    'model' => 'string',
                    'username' => 'string',
                    'password' => 'string',
                    'group' => 'group',
                ],
                'vars_map' => 'array',
                'headers' => [
                    'X-Auth-Token' => 'string',
                ],
            ],
            'mysql' => [
                'adapter' => 'string',
                'database' => 'string',
                'table' => 'string',
                'user' => 'string',
                'password' => 'password',
                'map' => [
                    'name' => 'string',
                    'model' => 'string',
                    'username' => 'string',
                    'password' => 'string',
                    'group' => 'group',
                ],
                'vars_map' => 'array',
            ],
        ],
        'model_map' => 'array',
        'next_adds_job' => 'boolean',
        'hooks' => 'array',
    ];

    if ($wanted_leaf !== false) {
        $valid_config_tmp = $wanted_leaf;
    } else {
        $valid_config_tmp = $valid_config;
    }

    $output = [];
    foreach ($tree as $leaf => $value) {
        if (is_array($tree[$leaf]) && ($valid_config_tmp !== 'array' && $valid_config_tmp[$leaf] !== 'array')) {
            $tmp_output = validate_oxidized_cfg($tree[$leaf], $valid_config_tmp[$leaf]);
            if (is_array($tmp_output)) {
                $output = array_merge($output, $tmp_output);
            }
        } else {
            if (! isset($valid_config_tmp[$leaf]) && ($valid_config_tmp !== 'array' && $valid_config_tmp[$leaf] !== 'array')) {
                $output[] = "$leaf - is not valid";
            }
        }
    }
    if (! empty($output)) {
        return $output;
    }
}
