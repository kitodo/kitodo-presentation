"""
Basic Sphinx configuration for TYPO3 theme. Uses configuration from Settings.cfg.
"""

import ConfigParser

settings = globals()
settings['general'] = settings

config = ConfigParser.RawConfigParser()
config.read('../../Documentation/Settings.cfg')

for section in config.sections():
    target = settings.setdefault(section, {})

    for (name, value) in config.items(section):
        if section == 'intersphinx_mapping':
            target[name] = (value, None)
        else:
            target[name] = value

extensions = [
    'sphinx_typo3_theme',
    'sphinx.ext.intersphinx',
    'sphinxcontrib.t3fieldlisttable'
]

html_theme = 'sphinx_typo3_theme'

# The TYPO3 convention is to capitalize the index filename, which would require
# us to visit http://127.0.0.1:8000/Index.html. We thus add an `index.html` that
# redirects to `Index.html`.
master_doc = 'Index'
html_extra_path = ['index.html']
exclude_patterns = []
