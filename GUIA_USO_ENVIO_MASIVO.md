 📧 Guía de Uso - Envío Masivo de Correos

## Casos de Uso Comunes

### 📌 Caso 1: Enviar anuncio a todos los estudiantes

**Pasos:**
1. Marcar ✅ "Enviar a Alumnos"
2. NO seleccionar ningún grupo (dejar en blanco)
3. Todos los estudiantes estarán pre-seleccionados
4. Escribir el mensaje
5. Enviar

**Resultado:** Todos los 84 estudiantes recibirán el correo en BCC

---

### 📌 Caso 2: Enviar notificación solo a un grupo específico

**Pasos:**
1. Marcar ✅ "Enviar a Alumnos"
2. Seleccionar el grupo deseado del dropdown
3. Solo los estudiantes de ese grupo aparecerán pre-seleccionados
4. Escribir el mensaje
5. Enviar

**Resultado:** Solo los estudiantes del grupo seleccionado recibirán el correo

---

### 📌 Caso 3: Enviar circular a todos los profesores

**Pasos:**
1. Marcar ✅ "Enviar a Profesores"
2. Todos los profesores estarán pre-seleccionados
3. Escribir el mensaje
4. Enviar

**Resultado:** Todos los 10 profesores recibirán el correo en BCC

---

### 📌 Caso 4: Enviar a estudiantes Y profesores

**Pasos:**
1. Marcar ✅ "Enviar a Alumnos"
2. Marcar ✅ "Enviar a Profesores"
3. (Opcional) Filtrar por grupo para alumnos
4. Todos estarán pre-seleccionados
5. Escribir el mensaje
6. Enviar

**Resultado:** Todos los seleccionados (alumnos + profesores) recibirán el correo en BCC

---

### 📌 Caso 5: Enviar solo a algunos estudiantes específicos

**Pasos:**
1. Marcar ✅ "Enviar a Alumnos"
2. (Opcional) Seleccionar grupo para reducir la lista
3. **Desmarcar todos** usando el botón de toggle masivo
4. Usar la búsqueda para encontrar los estudiantes específicos
5. Marcar solo los estudiantes deseados
6. Escribir el mensaje
7. Enviar

**Resultado:** Solo los estudiantes marcados recibirán el correo

---

### 📌 Caso 6: Enviar con archivos adjuntos

**Pasos:**
1. Seleccionar destinatarios (alumnos/profesores)
2. Escribir el mensaje
3. Hacer clic en "Archivos Adjuntos"
4. Subir hasta 5 archivos (máx 10MB cada uno)
5. Enviar

**Resultado:** Los destinatarios recibirán el correo con los archivos adjuntos

---

## 💡 Consejos Útiles

### Formato del Mensaje
El editor HTML permite:
- **Negrita**: Para destacar información importante
- *Cursiva*: Para énfasis
- Listas numeradas y con viñetas
- Enlaces clickeables
- Encabezados para organizar contenido
- Citas para destacar información

### Búsqueda de Destinatarios
- Usa la barra de búsqueda para filtrar por nombre
- Los resultados se actualizan en tiempo real
- Funciona tanto para alumnos como profesores

### Selección Masiva
- **Toggle All**: Selecciona/deselecciona todos de una vez
- Útil cuando quieres enviar a todos menos algunos
- Primero "deselecciona todos", luego marca los específicos

### Filtro por Grupo
- Reduce la lista a solo estudiantes del grupo
- Útil para avisos específicos de clase
- Los estudiantes del grupo se pre-seleccionan automáticamente

---

## ⚠️ Errores Comunes

### "Debes seleccionar al menos un destinatario"
**Causa:** No hay ningún checkbox marcado
**Solución:** Marca al menos un estudiante o profesor

### "Los destinatarios seleccionados no tienen correos electrónicos válidos"
**Causa:** Los usuarios seleccionados no tienen email en su perfil
**Solución:** Verifica que los destinatarios tengan emails válidos en el sistema

### Error al enviar
**Causa:** Problema con la configuración de correo del servidor
**Solución:** Contacta al administrador del sistema para revisar la configuración SMTP

---

## 📊 Ejemplo de Mensaje

```html
<h2>Estimada Comunidad ISUBJ</h2>

<p>Les informamos que el próximo <strong>viernes 30 de marzo</strong> no habrá clases por motivo de:</p>

<ul>
  <li>Junta de consejo técnico</li>
  <li>Capacitación docente</li>
</ul>

<p>Las clases se reanudarán el lunes 2 de abril.</p>

<p><em>Atentamente,</em><br>
La Dirección</p>
```

---

## 🔒 Seguridad y Privacidad

- ✅ Solo super_admin puede acceder a esta funcionalidad
- ✅ Los correos de los destinatarios NO son visibles entre ellos (BCC)
- ✅ Las copias institucionales siempre se incluyen
- ✅ No se guarda registro en base de datos por privacidad
- ✅ Los archivos adjuntos se eliminan después del envío

---

## 📈 Resumen Después del Envío

Después de enviar, verás una notificación como:

```
✅ Correo Enviado Exitosamente

Se envió el correo a 94 destinatarios (84 alumnos, 10 profesores).
```

El formulario se limpiará automáticamente y podrás enviar un nuevo correo.

---

**¿Necesitas ayuda?** Contacta al administrador del sistema.

