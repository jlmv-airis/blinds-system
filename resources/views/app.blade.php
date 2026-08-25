<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="overflow-y: auto;">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content= "Blinds System" />
        <meta name="robots" content= "index, follow">
        <link rel="shortcut icon" href="/img/icon.png" />
        @php $v = time(); @endphp
        <link rel="stylesheet" href="/v/0.3.16/css/app.css?v={{ $v }}"/>
        <link rel="stylesheet" href="/v/0.3.16/css/materialdesignicons.css?v={{ $v }}"/>

        <meta http-equiv="Expires" content="0">
        <meta http-equiv="Last-Modified" content="0">
        <meta http-equiv="Cache-Control" content="no-cache, mustrevalidate">
        <meta http-equiv="Pragma" content="no-cache">

        <title>Blinds System</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;300;400;500;600;700&display=swap" rel="stylesheet">


        <style>
            * {
                text-transform: none !important;
            }
            #app {
                font-family: 'Inter', sans-serif;
                width: 100% !important;
            }
            .imgLogo img {
                max-width: 220px !important;
                height: auto !important;
            }
        </style>
    </head>
    <body>
        <div id="app">
            <App/>
        </div>
    </body>
    <script src="/v/0.3.16/js/app.js?v={{ $v }}"></script>

<script>
(function() {
    var waitForRouter = setInterval(function() {
        var el = document.getElementById('app');
        if (el && el.__vue__ && el.__vue__.$router) {
            clearInterval(waitForRouter);
            var router = el.__vue__.$router;
            var app = el.__vue__;

            var hasRoute = router.options.routes.some(function(r) {
                return r.path === '/quotations/articles';
            });
            if (hasRoute) return;

            function getToken() {
                var accessData = app.$store.getters['auth/getAccessData'];
                return (accessData && accessData.token) || '';
            }

            function csrfToken() {
                var match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
                var token = match ? decodeURIComponent(match[1]) : '';
                if (!token) {
                    var meta = document.querySelector('meta[name="csrf-token"]');
                    token = meta ? meta.getAttribute('content') : '';
                }
                return token;
            }

            function apiPost(url, data) {
                var headers = { 'Authorization': 'Bearer ' + getToken(), 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest' };
                return fetch(url, { method: 'POST', headers: headers, body: JSON.stringify(data) })
                    .then(function(r) {
                        if (r.status === 401) {
                            window.location.href = '/login';
                            return { success: false };
                        }
                        return r.json();
                    });
            }

            var ArticlesCRUD = {
                name: 'QuotationsArticles',
                data: function() {
                    return {
                        articles: [],
                        search: '',
                        loading: false,
                        dialog: false,
                        editedItem: { id: null, article: '', sku: '', price: 0, price_list_2: 0, cost: 0 },
                        defaultItem: { id: null, article: '', sku: '', price: 0, price_list_2: 0, cost: 0 },
                        headers: [
                            { text: 'ID', value: 'id', class: 'grey--text', width: 60 },
                            { text: 'Artículo', value: 'article' },
                            { text: 'SKU', value: 'sku' },
                            { text: 'Categoría', value: 'categoria' },
                            { text: 'Modelo', value: 'model' },
                            { text: 'Unidad', value: 'unit' },
                            { text: 'Precio L1', value: 'price', align: 'right' },
                            { text: 'Precio L2', value: 'price_list_2', align: 'right' },
                            { text: 'Costo', value: 'cost', align: 'right' },
                            { text: 'Acciones', value: 'actions', sortable: false, align: 'center', width: 100 }
                        ]
                    };
                },
                computed: {
                    filteredArticles: function() {
                        var self = this;
                        if (!self.search) return self.articles;
                        var q = self.search.toLowerCase();
                        return self.articles.filter(function(a) {
                            return (a.article && a.article.toLowerCase().indexOf(q) >= 0) ||
                                   (a.sku && a.sku.toLowerCase().indexOf(q) >= 0) ||
                                   (a.model && a.model.toLowerCase().indexOf(q) >= 0) ||
                                   (a.categoria && a.categoria.toLowerCase().indexOf(q) >= 0);
                        });
                    },
                },
                methods: {
                    getArticles: function() {
                        var self = this;
                        self.loading = true;
                        apiPost('/quotations/getArticles', {}).then(function(res) {
                            if (res && res.success && res.articles) {
                                self.articles = res.articles;
                            }
                            self.loading = false;
                        }).catch(function() {
                            self.loading = false;
                        });
                    },
                    editItem: function(item) {
                        this.editedItem = Object.assign({}, item);
                        this.dialog = true;
                    },
                    newItem: function() {
                        this.editedItem = Object.assign({}, this.defaultItem);
                        this.dialog = true;
                    },
                    save: function() {
                        var self = this;
                        var isNew = !self.editedItem.id;
                        var url = isNew ? '/quotations/saveArticle' : '/quotations/updateArticle';
                        apiPost(url, self.editedItem).then(function(res) {
                            if (res && res.success) {
                                self.dialog = false;
                                self.getArticles();
                            }
                        });
                    },
                    deleteItem: function(item) {
                        var self = this;
                        if (!confirm('Eliminar artículo ' + item.article + '?')) return;
                        apiPost('/quotations/deleteArticle', { id: item.id }).then(function(res) {
                            if (res && res.success) {
                                self.getArticles();
                            }
                        });
                    }
                },
                mounted: function() {
                    this.getArticles();
                    this.getCatalogs();
                },
                render: function(h) {
                    var self = this;
                    return h('v-container', { props: { fluid: true } }, [
                        h('v-card', [
                            h('v-card-title', [
                                h('v-btn', { props: { color: 'primary', text: true }, on: { click: function() { self.$router.push('/'); } } }, [
                                    h('v-icon', { props: { left: true } }, 'mdi-arrow-left'),
                                    'Volver'
                                ]),
                                h('v-spacer'),
                                h('v-text-field', {
                                    props: { value: self.search, appendIcon: 'mdi-magnify', label: 'Buscar art\u00edculo', singleLine: true, hideDetails: true },
                                    style: { maxWidth: '400px' },
                                    on: { input: function(v) { self.search = v; } }
                                }),
                                h('v-spacer'),
                                h('v-btn', { props: { color: 'primary', dark: true }, on: { click: self.newItem } }, [
                                    h('v-icon', { props: { left: true } }, 'mdi-plus'),
                                    'Nuevo'
                                ])
                            ]),
                            h('v-data-table', {
                                props: {
                                    headers: self.headers,
                                    items: self.filteredArticles,
                                    loading: self.loading,
                                    loadingText: 'Cargando art\u00edculos...',
                                    'items-per-page-text': 'Art\u00edculos por p\u00e1gina',
                                    'page-text': '{0}-{1} de {2}',
                                    dense: true
                                },
                                scopedSlots: {
                                    'item.price': function(props) { return '$' + (props.item.price || 0); },
                                    'item.price_list_2': function(props) { return props.item.price_list_2 ? '$' + props.item.price_list_2 : '-'; },
                                    'item.cost': function(props) { return '$' + (props.item.cost || 0); },
                                    'item.actions': function(props) {
                                        return h('span', [
                                            h('v-icon', { props: { small: true }, class: 'mr-2', on: { click: function() { self.editItem(props.item); } } }, 'mdi-pencil'),
                                            h('v-icon', { props: { small: true }, on: { click: function() { self.deleteItem(props.item); } } }, 'mdi-delete')
                                        ]);
                                    }
                                }
                            }),
                            h('v-card-text', { staticClass: 'pt-1 pb-2' }, [
                                h('div', { staticClass: 'text-caption grey--text' }, 'Costo = gasto interno | L1 = precio venta local | L2 = precio venta for\u00e1nea')
                            ])
                        ]),
                        h('v-dialog', {
                            props: { value: self.dialog, maxWidth: '800px' },
                            on: { input: function(v) { self.dialog = v; } }
                        }, [
                            h('v-card', [
                                h('v-card-title', [
                                    h('span', { class: 'headline' }, self.editedItem.id ? 'Editar Art\u00edculo' : 'Nuevo Art\u00edculo')
                                ]),
                                h('v-card-text', [
                                    h('v-container', [
                                        h('v-row', [
                                            h('v-col', { props: { cols: 12 } }, [
                                                h('v-text-field', { props: { value: self.editedItem.article, label: 'Art\u00edculo', required: true }, on: { input: function(v) { self.editedItem.article = v; } } })
                                            ]),
                                            h('v-col', { props: { cols: 12 } }, [
                                                h('v-text-field', { props: { value: self.editedItem.sku, label: 'SKU' }, on: { input: function(v) { self.editedItem.sku = v; } } })
                                            ]),
                                            h('v-col', { props: { cols: 4 } }, [
                                                h('v-text-field', { props: { value: self.editedItem.price, label: 'Precio L1', type: 'number', prefix: '$' }, on: { input: function(v) { self.editedItem.price = v; } } })
                                            ]),
                                            h('v-col', { props: { cols: 4 } }, [
                                                h('v-text-field', { props: { value: self.editedItem.price_list_2, label: 'Precio L2', type: 'number', prefix: '$' }, on: { input: function(v) { self.editedItem.price_list_2 = v; } } })
                                            ]),
                                            h('v-col', { props: { cols: 4 } }, [
                                                h('v-text-field', { props: { value: self.editedItem.cost, label: 'Costo', type: 'number', prefix: '$' }, on: { input: function(v) { self.editedItem.cost = v; } } })
                                            ])
                                        ])
                                    ])
                                ]),
                                h('v-card-actions', [
                                    h('v-spacer'),
                                    h('v-btn', { props: { text: true }, on: { click: function() { self.dialog = false; } } }, 'Cancelar'),
                                    h('v-btn', { props: { color: 'primary' }, on: { click: self.save } }, 'Guardar')
                                ])
                            ])
                        ])
                    ]);
                }
            };

            var LocalInventoryCRUD = {
                name: 'LocalInventory',
                data: function() {
                    return {
                        items: [],
                        search: '',
                        loading: false,
                        dialog: false,
                        importDialog: false,
                        importing: false,
                        importFile: null,
                        importCompany: 1,
                        importResult: null,
                        companies: [
                            { id: 1, name: 'Lanson Shades' },
                            { id: 2, name: 'Indigoff (RT)' },
                            { id: 4, name: 'Indigoff' },
                            { id: 5, name: 'Wrks' }
                        ],
                        editedItem: { id: null, companie_id: 1, sku: '', product: '', unit: '', stock: 0, lots_text: '' },
                        defaultItem: { id: null, companie_id: 1, sku: '', product: '', unit: '', stock: 0, lots_text: '' },
                        headers: [
                            { text: 'Compa\u00f1\u00eda', value: 'companie_id', width: 120 },
                            { text: 'SKU', value: 'sku' },
                            { text: 'Producto', value: 'product' },
                            { text: 'Unidad', value: 'unit', width: 80 },
                            { text: 'Stock', value: 'stock', align: 'right', width: 90 },
                            { text: 'Lotes', value: 'lots_text' },
                            { text: 'Acciones', value: 'actions', sortable: false, align: 'center', width: 100 }
                        ]
                    };
                },
                computed: {
                    filteredItems: function() {
                        var self = this;
                        if (!self.search) return self.items;
                        var q = self.search.toLowerCase();
                        return self.items.filter(function(a) {
                            return (a.sku && a.sku.toLowerCase().indexOf(q) >= 0) ||
                                   (a.product && a.product.toLowerCase().indexOf(q) >= 0);
                        });
                    }
                },
                methods: {
                    companyName: function(id) {
                        var c = this.companies.find(function(x) { return x.id == id; });
                        return c ? c.name : id;
                    },
                    getItems: function() {
                        var self = this;
                        self.loading = true;
                        apiPost('/warehouses/getLocalInventory', {}).then(function(res) {
                            if (res && res.success && res.inventory) {
                                self.items = res.inventory.map(function(it) {
                                    it.lots_text = (it.lots || []).map(function(l) { return l.lot + ': ' + l.stock; }).join(', ');
                                    it.company_name = self.companyName(it.companie_id);
                                    return it;
                                });
                            }
                            self.loading = false;
                        }).catch(function() { self.loading = false; });
                    },
                    editItem: function(item) {
                        var lotsText = (item.lots || []).map(function(l) { return l.lot + ':' + l.stock; }).join('\n');
                        this.editedItem = {
                            id: item.id, companie_id: item.companie_id, sku: item.sku,
                            product: item.product, unit: item.unit, stock: item.stock, lots_text: lotsText
                        };
                        this.dialog = true;
                    },
                    newItem: function() {
                        this.editedItem = Object.assign({}, this.defaultItem);
                        this.dialog = true;
                    },
                    save: function() {
                        var self = this;
                        apiPost('/warehouses/saveLocalInventory', self.editedItem).then(function(res) {
                            if (res && res.success) {
                                self.dialog = false;
                                self.getItems();
                            }
                        });
                    },
                    deleteItem: function(item) {
                        var self = this;
                        if (!confirm('Desactivar ' + item.sku + '?')) return;
                        apiPost('/warehouses/deleteLocalInventory', { id: item.id }).then(function(res) {
                            if (res && res.success) { self.getItems(); }
                        });
                    },
                    openImport: function() {
                        this.importFile = null;
                        this.importResult = null;
                        this.importDialog = true;
                    },
                    pickFile: function() { this.$refs.csvInput.click(); },
                    onFileChange: function(e) {
                        if (e.target.files && e.target.files.length > 0) { this.importFile = e.target.files[0]; }
                    },
                    doImport: function() {
                        var self = this;
                        if (!self.importFile) return;
                        self.importing = true;
                        self.importResult = null;
                        var fd = new FormData();
                        fd.append('file', self.importFile);
                        fd.append('companie_id', self.importCompany);
                        fetch('/warehouses/importLocalInventory', {
                            method: 'POST',
                            headers: { 'Authorization': 'Bearer ' + getToken(), 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest' },                            body: fd
                        }).then(function(r) { return r.json(); }).then(function(res) {
                            self.importing = false;
                            if (res && res.success) {
                                self.importResult = 'Importado: ' + res.inserted + ' nuevos, ' + res.updated + ' actualizados.';
                                self.getItems();
                            } else {
                                self.importResult = 'Error: ' + ((res && res.message) || 'no se pudo importar');
                            }
                        }).catch(function() { self.importing = false; self.importResult = 'Error de red'; });
                    },
                    downloadTemplate: function() {
                        var csv = 'sku,producto,unidad,stock,lote,cantidad_lote\n';
                        csv += '02TX211-WH-2.90M,SHEER FUSION DIMOUT WHITE 2.90 mts,ML,7.2,L-2231,2.9\n';
                        csv += '02TX211-WH-2.90M,SHEER FUSION DIMOUT WHITE 2.90 mts,ML,7.2,L-2232,1.5\n';
                        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                        var a = document.createElement('a');
                        a.href = URL.createObjectURL(blob);
                        a.download = 'plantilla_inventario.csv';
                        a.click();
                    }
                },
                mounted: function() { this.getItems(); },
                render: function(h) {
                    var self = this;
                    return h('v-container', { props: { fluid: true } }, [
                        h('input', { ref: 'csvInput', attrs: { type: 'file', accept: '.csv,text/csv' }, style: { display: 'none' }, on: { change: self.onFileChange } }),
                        h('v-card', [
                            h('v-card-title', [
                                h('v-btn', { props: { color: 'primary', text: true }, on: { click: function() { self.$router.push('/'); } } }, [
                                    h('v-icon', { props: { left: true } }, 'mdi-arrow-left'),
                                    'Volver'
                                ]),
                                h('div', { staticClass: 'title ml-4' }, 'Inventario Local'),
                                h('v-spacer'),
                                h('v-text-field', {
                                    props: { value: self.search, appendIcon: 'mdi-magnify', label: 'Buscar', singleLine: true, hideDetails: true },
                                    style: { maxWidth: '300px' },
                                    on: { input: function(v) { self.search = v; } }
                                }),
                                h('v-btn', { props: { color: 'primary', class: 'ml-4' }, on: { click: self.newItem } }, [
                                    h('v-icon', { props: { left: true } }, 'mdi-plus'), 'Nuevo'
                                ])
                            ]),
                            h('v-card-actions', { staticClass: 'pt-0 pb-2' }, [
                                h('span', { staticClass: 'text-caption grey--text ml-4' }, 'Se usa como respaldo cuando el ERP no est\u00e1 disponible.'),
                                h('v-spacer'),
                                h('v-btn', { props: { small: true, text: true, color: 'secondary' }, on: { click: self.downloadTemplate } }, [
                                    h('v-icon', { props: { left: true, small: true } }, 'mdi-download'), 'Plantilla CSV'
                                ]),
                                h('v-btn', { props: { small: true, outlined: true, color: 'accent', class: 'mr-4' }, on: { click: self.openImport } }, [
                                    h('v-icon', { props: { left: true, small: true } }, 'mdi-upload'), 'Importar CSV'
                                ])
                            ]),
                            h('v-data-table', {
                                props: {
                                    headers: self.headers,
                                    items: self.filteredItems,
                                    loading: self.loading,
                                    loadingText: 'Cargando inventario...',
                                    'items-per-page-text': 'Art\u00edculos por p\u00e1gina',
                                    'page-text': '{0}-{1} de {2}',
                                    dense: true
                                },
                                scopedSlots: {
                                    'item.lots_text': function(props) {
                                        return h('span', { staticClass: 'grey--text' }, props.item.lots_text || '\u2014');
                                    },
                                    'item.actions': function(props) {
                                        return h('span', [
                                            h('v-icon', { props: { small: true }, class: 'mr-2', on: { click: function() { self.editItem(props.item); } } }, 'mdi-pencil'),
                                            h('v-icon', { props: { small: true }, on: { click: function() { self.deleteItem(props.item); } } }, 'mdi-delete')
                                        ]);
                                    }
                                }
                            })
                        ]),
                        // DIALOG EDITAR/NUEVO
                        h('v-dialog', {
                            props: { value: self.dialog, maxWidth: '600px' },
                            on: { input: function(v) { self.dialog = v; } }
                        }, [
                            h('v-card', [
                                h('v-card-title', { staticClass: 'headline' }, self.editedItem.id ? 'Editar art\u00edculo' : 'Nuevo art\u00edculo'),
                                h('v-card-text', [
                                    h('v-container', [
                                        h('v-row', [
                                            h('v-col', { props: { cols: 6 } }, [
                                                h('v-select', { props: { value: self.editedItem.companie_id, items: self.companies, itemValue: 'id', itemText: 'name', label: 'Compa\u00f1\u00eda' }, on: { input: function(v) { self.editedItem.companie_id = v; } } })
                                            ]),
                                            h('v-col', { props: { cols: 6 } }, [
                                                h('v-text-field', { props: { value: self.editedItem.sku, label: 'SKU', required: true }, on: { input: function(v) { self.editedItem.sku = v; } } })
                                            ]),
                                            h('v-col', { props: { cols: 12 } }, [
                                                h('v-text-field', { props: { value: self.editedItem.product, label: 'Producto' }, on: { input: function(v) { self.editedItem.product = v; } } })
                                            ]),
                                            h('v-col', { props: { cols: 6 } }, [
                                                h('v-text-field', { props: { value: self.editedItem.unit, label: 'Unidad' }, on: { input: function(v) { self.editedItem.unit = v; } } })
                                            ]),
                                            h('v-col', { props: { cols: 6 } }, [
                                                h('v-text-field', { props: { value: self.editedItem.stock, label: 'Stock', type: 'number' }, on: { input: function(v) { self.editedItem.stock = v; } } })
                                            ]),
                                            h('v-col', { props: { cols: 12 } }, [
                                                h('v-textarea', {
                                                    props: { value: self.editedItem.lots_text, label: 'Lotes (uno por l\u00ednea: LOTE:cantidad)', rows: 3, hint: 'Ej: L-2231:2.9', 'persistent-hint': true, outlined: true, dense: true },
                                                    on: { input: function(v) { self.editedItem.lots_text = v; } }
                                                })
                                            ])
                                        ])
                                    ])
                                ]),
                                h('v-card-actions', [
                                    h('v-spacer'),
                                    h('v-btn', { props: { text: true }, on: { click: function() { self.dialog = false; } } }, 'Cancelar'),
                                    h('v-btn', { props: { color: 'primary' }, on: { click: self.save } }, 'Guardar')
                                ])
                            ])
                        ]),
                        // DIALOG IMPORTAR
                        h('v-dialog', {
                            props: { value: self.importDialog, maxWidth: '500px' },
                            on: { input: function(v) { self.importDialog = v; } }
                        }, [
                            h('v-card', [
                                h('v-card-title', { staticClass: 'headline' }, 'Importar inventario CSV'),
                                h('v-card-text', [
                                    h('p', { staticClass: 'text-caption grey--text' }, 'Formato: sku,producto,unidad,stock,lote,cantidad_lote (una fila por lote o sin lote).'),
                                    h('v-select', { props: { value: self.importCompany, items: self.companies, itemValue: 'id', itemText: 'name', label: 'Compa\u00f1\u00eda' }, on: { input: function(v) { self.importCompany = v; } } }),
                                    h('v-btn', { props: { color: 'primary', block: true }, on: { click: self.pickFile } }, [
                                        h('v-icon', { props: { left: true } }, 'mdi-file-upload-outline'),
                                        self.importFile ? self.importFile.name : 'Seleccionar archivo CSV'
                                    ]),
                                    self.importResult ? h('v-alert', { props: { dense: true, type: self.importResult.indexOf('Error') === 0 ? 'error' : 'success', class: 'mt-4 mb-0' } }, self.importResult) : null
                                ]),
                                h('v-card-actions', [
                                    h('v-spacer'),
                                    h('v-btn', { props: { text: true }, on: { click: function() { self.importDialog = false; } } }, 'Cerrar'),
                                    h('v-btn', { props: { color: 'primary', loading: self.importing, disabled: !self.importFile }, on: { click: self.doImport } }, 'Importar')
                                ])
                            ])
                        ])
                    ]);
                }
            };

            router.addRoutes([
                { path: '/quotations/articles', name: 'QuotationsArticles', component: ArticlesCRUD },
                { path: '/inventory/local', name: 'LocalInventory', component: LocalInventoryCRUD }
            ]);

            var dynamicPaths = ['/quotations/articles', '/inventory/local'];
            if (dynamicPaths.indexOf(window.location.pathname) >= 0 && router.currentRoute.path !== window.location.pathname) {
                setTimeout(function() {
                    router.replace(window.location.pathname).catch(function(){});
                }, 100);
            }
        }
    }, 300);
})();
</script>
</html>