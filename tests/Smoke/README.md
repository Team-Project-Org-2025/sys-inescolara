# Suite de humo

`ModelsSmokeTest.php` contiene **31 casos de humo**, uno por cada modelo, en el
mismo orden del sidebar (ver [`../GUIA_PRUEBAS.md`](../GUIA_PRUEBAS.md)).

Cada caso se ejecuta con dos métodos del data provider:

1. `el_modelo_cumple_su_contrato_basico`: autoload, archivo, herencia de
   `Database`, interfaces y métodos públicos.
2. `el_doble_sin_base_de_datos_es_instanciable`: existencia y creación del doble
   `NoDb<Modelo>` sin MySQL.

Además hay un test de control (`todos_los_modelos_del_directorio_estan_cubiertos`)
que verifica que no falte ningún modelo de `app/models`.

Por eso la suite reporta **63 pruebas** (31 + 31 + 1), pero la matriz de
trazabilidad registra **31 casos de humo** (`CP-SMOKE-01` a `CP-SMOKE-31`).

```powershell
vendor\bin\phpunit -c tests\phpunit.xml --testsuite Smoke --do-not-cache-result
```

Resultado esperado: `OK (63 tests, 644 assertions)`.
