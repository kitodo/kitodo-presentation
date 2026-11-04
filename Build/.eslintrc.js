module.exports = {
    "env": {
        "browser": true,
        "es2021": true
    },
    "extends": [
        "eslint:recommended",
        "plugin:compat/recommended",
        "plugin:import/recommended",
        "plugin:@typescript-eslint/recommended"
    ],
    "ignorePatterns": [
        // Generated/bundled files are not linted.
        "Resources/Public/JavaScript/DlfMediaPlayer/DlfMediaVendor.js",
        // Third-party libraries are not linted.
        "Resources/Public/JavaScript/Embedded3dViewer/",
        "Resources/Public/JavaScript/Gridstack/",
        "Resources/Public/JavaScript/HtmlMidiPlayer/",
        "Resources/Public/JavaScript/OpenLayers/",
        "Resources/Public/JavaScript/Verovio/",
        "Resources/Public/JavaScript/jQuery/",
        "Resources/Public/JavaScript/jQueryUI/",
        "Resources/Public/JavaScript/Toastify/"
    ],
    "overrides": [
        {
            "env": {
                "node": true
            },
            "files": [
                ".eslintrc.{js,cjs}"
            ],
            "parserOptions": {
                "sourceType": "script"
            }
        }
    ],
    "parser": "@typescript-eslint/parser",
    "parserOptions": {
        "ecmaVersion": "latest",
        "sourceType": "module"
    },
    "plugins": [
        "compat",
        "import",
        "@typescript-eslint"
    ],
    "rules": {
        "compat/compat": "error",
        // The no-underscore-dangle rule (enforced by Codacy) flags every
        // property access with a leading or trailing underscore. jQuery UI
        // plugins and several first-party components rely on jQuery UI's
        // private-method convention (_method, _super, etc.), so the rule is
        // disabled; the remaining private-member style is enforced by code
        // review.
        "no-underscore-dangle": "off",
        // turn on errors for missing imports
        "import/no-unresolved": "error",
    },
    "settings": {
        "eslint-target-browser": [
            "ie >= 11",
            "not op_mini all"
        ],
        "import/resolver": {
          "babel-module": {}
        }
    }
}
