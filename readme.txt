===  Grupo Logistico Andreani ===
Donate link: 
Tags: woocommerce, shipping, rates, andreani, grupo logistico andreani, envio, envios, logistica, ultima milla, retiros, pickup, colecta, distribucion, supply chain, despacho, transporte, entrega, puntos HOP, entrega en sucursal, tarifas, precios, costos, cotizacion
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 2.0
Copyright: 2025 Andreani / OpenDev PRo
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Plugin oficial de Andreani. Simplifica la gestión de tus envíos con Andreani. Este plugin te permite gestionar fácilmente todas tus entregas, optimizando tu logística, con una experiencia de envío confiable y eficiente para tu tienda WooCommerce.


== Description ==
Plugin oficial de Andreani. Simplifica la gestión de tus envíos con Andreani. Este plugin te permite gestionar fácilmente todas tus entregas, optimizando tu logística, con una experiencia de envío confiable y eficiente para tu tienda WooCommerce.
Funcionalidades incluidas: 
-Cálculo de tarifas de envío
-Selección de sucursales Andreani: Permite seleccionar una sucursal de Andreani para el caso de los envíos PAS (Puerta a sucursal)
-Generación de órdenes de envío en Andreani: Al realizar una compra se genera la orden en Andreani y su correspondiente etiqueta de envío.
Este plugin es un fork del Wanderlust – Integración para Andreani y WooCommerce. Agradecemos la colaboración de wanderlustcodes.

== External services ==
 Este plugin conecta con : 
Api Sucursales url:https://external-services.api.flexipaas.com/woo/seguridad/sucursales/ 
Obtiene las sucursales andreani que se pueden seleccionar desde la pagina checkout (finalizar compra).
Api Cotizaciones url : https://external-services.api.flexipaas.com/woo/seguridad/cotizaciones/  
Obtiene las tasas de envio andreani determinadas por el costo y la distancia del envio. Servicio accedido desde el carrito y tambien desde la pagina finalizar compra.
Request :{cp_origen= codigo postal , api_confirmarretiro=, valor_declarado= precio producto, api_nrocuenta= codigo cliente andreani , api_key=, operativa= numero de contrato PAS o PAP , peso_total= peso total del paquete, api_user= usuario andreani , volumen_total= volumen total producto , api_password= pasword andreani, cp_destino= codigo postal destino}
Response: {
    "pesoAforado": "10000.00",
    "tarifaSinIva": {
        "seguroDistribucion": "0.00",
        "distribucion": "19781.27",
        "total": "19781.27"
    },
    "tarifaConIva": {
        "seguroDistribucion": "0.00",
        "distribucion": "23935.34",
        "total": "23935.34"
    }
}
Api Orden url: https://external-services.api.flexipaas.com/woo/seguridad/orden/  
Envio de la orden a andreani . Recibe todos los datos de la orden . Devuelve un numero de seguimiento  y el link a su respectiva etiqueta. Servicio accedido al confirmar la compra .
Request:  {  "contrato": "399999999",  "idPedido": "",  "valorACobrar": "",  "origen": {   "postal": { "codigoPostal": "3378", "calle": "Av Falsa", "numero": "380", "localidad": "PUERTO ESPERANZA", "region": "", "pais": "Argentina", "componentesDeDireccion": [  {   "meta": "entreCalle",   "contenido": "Medina y Jualberto"  } ]   }  },  "destino": {   "postal": { "codigoPostal": "1292", "calle": "Macacha Guemes", "numero": "28", "localidad": "CIUDAD AUTONOMA DE BUENOS AIRES", "region": "AR-B", "pais": "Argentina", "componentesDeDireccion": [  {   "meta": "piso",   "contenido": "2"  },  {   "meta": "departamento",   "contenido": "B"  } ]   }  },  "remitente": {   "nombreCompleto": "Alberto Lopez",   "email": "remitente@andreani.com",   "documentoTipo": "DNI",   "documentoNumero": "33111222",   "telefonos": [ {  "tipo": 1,  "numero": "113332244" }   ]  },  "destinatario": [   { "nombreCompleto": "Empresa SA", "email": "alter@andreani.com", "documentoTipo": "CUIT", "documentoNumero": "30234567890", "telefonos": [  {   "tipo": 2,   "numero": "153111231"  } ]   }  ],  "remito": {   "numeroRemito": "123456789012R",  },  "bultos": [   { "kilos": 2, "largoCm": 10, "altoCm": 50, "anchoCm": 10, "volumenCm": 5000, "valorDeclaradoSinImpuestos": 1200, "valorDeclaradoConImpuestos": 1452, "referencias": [  {   "meta": "detalle",   "contenido": "Secador de pelo"  },   "contenido": "10000"  },  {   "meta": "observaciones",   "contenido": "color negro"  } ]   } ]}
Response: { "estado": "Creada",   "tipo": "B2C",   "sucursalDeDistribucion": { "nomenclatura": "BAR", "descripcion": "BARRACAS", "id": "46"   },   "sucursalDeRendicion": { "nomenclatura": "REN", "descripcion": "PROVEEDOR RENDICIONES", "id": "-1"   },   "sucursalDeImposicion": { "nomenclatura": "", "descripcion": "", "id": ""   },   "fechaCreacion": "2020-05-06T15:47:57-03:00",   "zonaDeReparto": "",   "numeroDePermisionaria": "RNPSP Nº 586",   "descripcionServicio": "Contrato de TEST WebService",   "etiquetaRemito": "",   "bultos": [ {  "numeroDeBulto": "1",  "numeroDeEnvio": "360000000036820",  "totalizador": "1/2",  "linking": [   {    "meta": "Etiqueta",    "contenido": "https://apisqa.andreani.com/v2/360000000036820/etiquetas"   }  ] }, {  "numeroDeBulto": "2",  "numeroDeEnvio": "360000000036830",  "totalizador": "2/2",  "linking": [   {    "meta": "Etiqueta",    "contenido": "https://apisqa.andreani.com/v2/360000000036830/etiquetas"   }  ] }   ],   "fechaEstimadaDeEntrega": "",   "huellaDeCarbono": "",   "gastoEnergetico": ""  }   


== Installation ==
1. Instale el plugin del Grupo Logistico Andreani .
2. Active el plugin.
3. Ingrese en la página de ajustes de WooCommerce. 
4. Acceda a la pestaña "Envío".
5. En Zonas de envío seleccione "Add zone" y luego Agregar método de envío -> Andreani
6. Active el check Andreani Envios.
7. Ingrese las configuraciones requeridas para operar con Andreani (código de cliente, usuario, contratos de servicio).
8. En Productos, ingrese las dimensiones que permiten calcular el costo de los envíos.
 
