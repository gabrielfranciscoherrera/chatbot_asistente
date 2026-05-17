-- =============================================================================
-- García Smester Web — Diccionario inicial del chatbot (14 entradas reales)
-- Requiere: 2026_05_04_100000_crear_tablas_base.sql ejecutado primero
-- =============================================================================

SET NAMES utf8mb4;

INSERT INTO chatbot_entradas
    (categoria, pregunta_tipo, palabras_clave, respuesta, tiene_botones, botones_json, prioridad)
VALUES

-- SERVICIOS ---------------------------------------------------------------

('servicios', 'Servicios generales',
 'piso,pisos,servicio,servicios,que hacen,que ofrecen,productos',
 'Ofrecemos sistemas de pisos industriales y decorativos: EpoGloss™, EpoQuartz™, TerrazzoPoxy™, FuturaStone™, BrightConcrete™, EstampCrete™, EpóxicoMetálico™, Revi-Stone™ y terminaciones para piscinas. ¿Sobre cuál desea información?',
 1,
 '[{"label":"Ver todos los servicios","url":"/pisos-industriales-epoxicos/"},{"label":"Llamar ahora","url":"tel:8095622566"}]',
 5),

('servicios', 'Pisos hospitalarios y asépticos',
 'hospital,hospitalario,aseptico,aséptico,quirofano,quirófano,clinica,clínica,medico,médico,sanitario,antimicrobiano,grado medico',
 'Nuestros pisos hospitalarios son epóxicos autonivelantes antimicrobianos con vida útil de 40 años. Certificados bajo normas FDA/USDA. Instalados en el Hospital Marcelino Vélez, Hospital La Victoria y Hospital Rodolfo de la Cruz Lora en Santo Domingo.',
 1,
 '[{"label":"Ver pisos hospitalarios","url":"/pisos-hospitalarios-asepticos/"},{"label":"Cotizar","url":"/contacto/"}]',
 1),

('servicios', 'Pisos industriales epóxicos',
 'industrial,fabrica,fábrica,planta,zona franca,almacen,almacén,trafico pesado,tráfico pesado,epogloss,epoQuartz,epoxido,epóxido,epoxico,epóxico',
 'Los sistemas EpoGloss™ y EpoQuartz™ resisten ácidos, solventes y tráfico pesado. Disponibles en 12 colores sólidos y combinaciones con arena de cuarzo antideslizante. Ideales para plantas industriales, almacenes y zonas francas.',
 1,
 '[{"label":"Ver EpoGloss / EpoQuartz","url":"/pisos-industriales-epoxicos/"},{"label":"Solicitar cotización","url":"/contacto/"}]',
 2),

('servicios', 'Pisos para piscinas',
 'piscina,pool,nadar,agua,acuatico,acuático,quartzpool,chipbrightpool',
 'Para piscinas ofrecemos QuartzPool™ (base cementosa con mármol y cuarzo) y ChipBrightPool™ (membrana elastomérica impermeable y flexible). Completamente impermeables, resistentes a productos químicos y al agua de mar.',
 1,
 '[{"label":"Ver sistemas para piscinas","url":"/piscinas/"},{"label":"Contactar","url":"/contacto/"}]',
 2),

('servicios', 'TerrazzoPoxy',
 'terrazo,terrazzo,terrazzopoxy,cuarzo,vidrio,nacar,nácar,sin juntas,continuo',
 'TerrazzoPoxy™ es un mortero epóxico con cuarzo, vidrios y nácar. Sin juntas, pulido a espejo, paneles continuos hasta 9 metros. Ideal para centros comerciales, aeropuertos, lobbies y espacios de alto tráfico peatonal.',
 1,
 '[{"label":"Ver TerrazzoPoxy™","url":"/terrazzopoxy/"},{"label":"Cotizar","url":"/contacto/"}]',
 2),

('servicios', 'Piso decorativo metálico 3D',
 'decorativo,hotel,apartamento,metalico,metálico,3d,tridimensional,lobby,residencial,epoxico metalico,epóxico metálico',
 'El piso EpóxicoMetálico™ utiliza pigmentos especiales que crean una apariencia tridimensional única. Cada instalación es irrepetible. Ideal para lobbies de hoteles, apartamentos de lujo y espacios VIP.',
 1,
 '[{"label":"Ver EpóxicoMetálico™","url":"/epoxico-metalico/"},{"label":"Contactar","url":"/contacto/"}]',
 3),

('servicios', 'FuturaStone',
 'futurastone,cantos rodados,canto rodado,exterior,antideslizante,jardin,jardín,terraza',
 'FUTURASTONE™ combina cantos rodados con epóxico 100% sólido. Antideslizante natural, resistente a la intemperie. Perfecto para exteriores, piscinas, jardines, accesos y terrazas.',
 1,
 '[{"label":"Ver FuturaStone™","url":"/futura-stone/"},{"label":"Cotizar","url":"/contacto/"}]',
 3),

('servicios', 'EstampCrete (hormigón estampado)',
 'estampcrete,hormigon estampado,hormigón estampado,pigmentado,plaza,adoquin,adoquín,parqueo,entrada vehicular',
 'ESTAMPCRETE™ es hormigón estampado y pigmentado para exteriores. Resiste el tráfico vehicular, los rayos UV y la intemperie. Usado en plazas, hoteles, entradas vehiculares y áreas comunes.',
 1,
 '[{"label":"Ver EstampCrete™","url":"/estampcrete/"},{"label":"Solicitar información","url":"/contacto/"}]',
 3),

('servicios', 'BrightConcrete (hormigón pulido)',
 'brightconcrete,hormigon pulido,hormigón pulido,diamante,durable,concreto pulido',
 'BRIGHTCONCRETE™ es hormigón pulido con diamante. 3 veces más durable que el concreto estándar. Superficie brillante y fácil de limpiar. Ideal para pisos de alto tráfico en almacenes, showrooms y zonas francas.',
 1,
 '[{"label":"Ver BrightConcrete™","url":"/bright-concrete/"},{"label":"Cotizar","url":"/contacto/"}]',
 3),

('servicios', 'NovaLimpia (mantenimiento)',
 'mantenimiento,limpiar,limpieza,novalimpia,restaurar,restauracion,restauración,renovar,renovacion,renovación',
 'NOVALIMPIA® es nuestro servicio de renovación, restauración y limpieza de pisos ya instalados: hormigón estampado, epóxicos, TerrazzoPoxy™, FuturaStone™ y adoquines. Devolvemos el aspecto original sin reemplazar el piso.',
 1,
 '[{"label":"Ver NovaLimpia®","url":"/novalimpia/"},{"label":"Solicitar visita","url":"/contacto/"}]',
 3),

-- PRECIOS -----------------------------------------------------------------

('precios', 'Precios y cotizaciones',
 'precio,costo,cuanto,cuánto,valor,cotizacion,cotización,cotizar,presupuesto,tarifa,cobran',
 'Los precios varían según el sistema elegido, el área a instalar y la condición del sustrato actual. Llame al 809-562-2566 o escríbanos a garcia.smester@gmail.com para recibir una cotización personalizada y sin compromiso.',
 1,
 '[{"label":"Llamar al 809-562-2566","url":"tel:8095622566"},{"label":"Enviar mensaje","url":"/contacto/"}]',
 1),

-- PROYECTOS ---------------------------------------------------------------

('proyectos', 'Proyectos y referencias',
 'proyecto,obra,trabajo,hicieron,hizo,referencia,ejemplo,portafolio,clientes,han trabajado',
 'Hemos trabajado en el Hospital Marcelino Vélez, Hospital La Victoria, Hospital Rodolfo de la Cruz Lora, centros comerciales, zonas francas y proyectos residenciales en toda la República Dominicana. Más de 35 años de trayectoria.',
 1,
 '[{"label":"Ver proyectos","url":"/proyectos/"},{"label":"Contactar","url":"/contacto/"}]',
 2),

-- CONTACTO ----------------------------------------------------------------

('contacto', 'Información de contacto',
 'contacto,llamar,telefono,teléfono,numero,número,comunicar,hablar,escribir,email,correo,whatsapp',
 'Puede contactarnos al 809-562-2566 o 809-563-8011. También por email: garcia.smester@gmail.com. Nuestra oficina está en Av. Lope de Vega #29, Torre Novocentro suite 704, Naco, Santo Domingo.',
 1,
 '[{"label":"Llamar","url":"tel:8095622566"},{"label":"WhatsApp","url":"https://wa.me/18095622566"},{"label":"Enviar mensaje","url":"/contacto/"}]',
 1),

-- GENERAL -----------------------------------------------------------------

('general', 'Historia y trayectoria',
 'años,experiencia,cuanto tiempo,cuánto tiempo,trayectoria,fundada,fundacion,fundación,historia,desde cuando,desde cuándo',
 'García Smester fue fundada en 1990 como empresa especializada en pisos industriales asépticos y decorativos. Con más de 35 años de trayectoria somos la empresa de mayor experiencia en sistemas de pisos epóxicos en la República Dominicana.',
 0, NULL, 5);
-- =============================================================================
-- García Smester Web — Entradas chatbot: saludos, empresa y construcción
-- Aplicar DESPUÉS de 2026_05_04_100001_seed_chatbot.sql
-- =============================================================================

SET NAMES utf8mb4;

INSERT INTO chatbot_entradas
    (categoria, pregunta_tipo, palabras_clave, respuesta, tiene_botones, botones_json, prioridad)
VALUES

-- SALUDOS -------------------------------------------------------------------

('general', 'Saludo inicial',
 'hola,holi,hello,hi,hey,buenas,buen dia,buen día,buenos dias,buenos días,buenas tardes,buenas noches,saludos,que tal,qué tal,como estan,cómo están,alo,alô',
 '¡Hola! Bienvenido a García Smester 👋 Somos especialistas en pisos epóxicos, hospitalarios y decorativos con más de 35 años en la República Dominicana. ¿En qué le puedo ayudar hoy?',
 1,
 '[{"texto":"Ver nuestros servicios","url":"/"},{"texto":"Solicitar cotización","url":"/contacto/"}]',
 1),

('general', 'Agradecimiento y despedida',
 'gracias,muchas gracias,ok gracias,perfecto,excelente,muy bien,hasta luego,adios,adiós,chao,bye,nos vemos',
 'De nada, con mucho gusto. Estamos a su disposición si necesita más información. ¡Que tenga un excelente día!',
 1,
 '[{"texto":"Contactar por WhatsApp","url":"https://wa.me/18095622566"},{"texto":"Solicitar cotización","url":"/contacto/"}]',
 2),

-- EMPRESA Y CONSTRUCCIÓN ----------------------------------------------------

('general', 'Qué es García Smester / presentación',
 'que es,qué es,que son,qué son,quienes son,quiénes son,quien eres,quién eres,a que se dedican,a qué se dedican,sobre ustedes,empresa,presentacion,presentación,nos pueden ayudar',
 'García Smester es una empresa dominicana fundada en 1990, especializada en la fabricación e instalación de sistemas de pisos epóxicos, decorativos e industriales para el sector hospitalario, comercial e industrial en todo el país. Contamos con más de 35 años de experiencia en la República Dominicana.',
 1,
 '[{"texto":"Ver nuestros servicios","url":"/"},{"texto":"Contactar","url":"/contacto/"}]',
 1),

('general', 'Sector construcción e industria',
 'construccion,construcción,obra,obras,edificio,edificios,hospital,hospitales,comercial,industria,industrial,manufactura,sector,remodelacion,remodelación,acabados,piso nuevo',
 'Trabajamos en proyectos de construcción y remodelación en todos los sectores: hospitales, clínicas, plantas industriales, zonas francas, centros comerciales, hoteles, restaurantes y residencias de lujo. Instalamos directamente sobre el sustrato de concreto existente.',
 1,
 '[{"texto":"Ver proyectos realizados","url":"/proyectos/"},{"texto":"Cotizar mi proyecto","url":"/contacto/"}]',
 2),

('general', 'Cobertura y zonas de trabajo',
 'donde trabajan,dónde trabajan,zona,zonas,area,área,republica dominicana,república dominicana,santiago,la romana,punta cana,puerto plata,san pedro,bani,baní,trabajan en',
 'Atendemos proyectos en toda la República Dominicana: Santo Domingo, Santiago, La Romana, Punta Cana, Puerto Plata, San Pedro de Macorís y más. Nuestra oficina principal está en Naco, Santo Domingo.',
 1,
 '[{"texto":"Ver ubicación","url":"/contacto/"},{"texto":"Llamar ahora","url":"tel:8095622566"}]',
 3),

('general', 'Garantía y durabilidad',
 'garantia,garantía,cuanto dura,cuánto dura,durable,durabilidad,vida util,vida útil,cuantos años,cuántos años,resistente,resistencia',
 'Nuestros sistemas epóxicos hospitalarios tienen una vida útil de hasta 40 años. Ofrecemos garantía sobre materiales y mano de obra. Los sistemas como BrightConcrete™ son 3× más durables que el concreto estándar.',
 1,
 '[{"texto":"Ver especificaciones","url":"/pisos-hospitalarios-asepticos/"},{"texto":"Contactar","url":"/contacto/"}]',
 3),

('general', 'Proceso de instalación / tiempo',
 'cuanto tiempo,cuánto tiempo,tiempo de instalacion,instalacion,instalación,proceso,como instalan,cómo instalan,dias,días,rapido,rápido,demora',
 'El tiempo de instalación depende del área y el sistema elegido. En general, un piso epóxico estándar se instala entre 1 y 3 días. El curado completo toma 7 días antes de tráfico pesado. Trabajamos sin juntas para mayor velocidad.',
 1,
 '[{"texto":"Solicitar visita técnica","url":"/contacto/"},{"texto":"Llamar al 809-562-2566","url":"tel:8095622566"}]',
 4),

-- VARIANTES DE NOMBRES DE PRODUCTOS -----------------------------------------

('servicios', 'EpoGloss y EpoQuartz (variantes)',
 'epoxi,epoxy,exposy,exposty,eposy,eposi,epoglos,epocloss,epoclass,epoclas,epoxi industrial,piso epoxi',
 'Creo que está preguntando por nuestros sistemas epóxicos. Los principales son EpoGloss™ (colores sólidos, alta resistencia química) y EpoQuartz™ (arena de cuarzo antideslizante). Ambos son ideales para plantas industriales, almacenes y áreas de alto tráfico.',
 1,
 '[{"texto":"Ver EpoGloss / EpoQuartz","url":"/pisos-industriales-epoxicos/"},{"texto":"Cotizar","url":"/contacto/"}]',
 2),

('servicios', 'TerrazzoPoxy (variantes)',
 'terrazopoxy,terrazoposi,terazo,terrazo poxy,terrazo epoxi,terrazo epoxy,piso terrazo,piso terrazzo',
 'TerrazzoPoxy™ es nuestro sistema de mortero epóxico con cuarzo, vidrios y nácar, pulido a espejo y sin juntas. Los paneles pueden llegar hasta 9 metros de largo. Perfecto para centros comerciales, lobbies y espacios de alta visibilidad.',
 1,
 '[{"texto":"Ver TerrazzoPoxy™","url":"/terrazzopoxy/"},{"texto":"Cotizar","url":"/contacto/"}]',
 2);
