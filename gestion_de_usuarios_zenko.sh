#!/bin/bash

# Gestion de usuarios del sistema - Proyecto SGDM (Zenko)
# Servidor: AlmaLinux 8.10 - Proxmox VE 8.4.5 (ITS Arias-Balparda)
# Uso: sudo ./gestion_de_usuarios_zenko.sh

LOG_FILE="/var/log/zenko_gestion_usuarios.log"
UID_MINIMO=1000

if [[ $EUID -ne 0 ]]; then
    echo "Este script debe ejecutarse como root (usa: sudo ./gestion_de_usuarios_zenko.sh)"
    exit 1
fi

# uits siempre corre esto con sudo, asi que $USER daria "root".
# Con SUDO_USER queda registrado quien lo ejecuto en verdad.
USUARIO_REAL="${SUDO_USER:-$USER}"

registrar_log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') | ejecutado por: $USUARIO_REAL | $1" >> "$LOG_FILE"
}

validar_nombre_usuario() {
    local nombre="$1"
    [[ "$nombre" =~ ^[a-z_][a-z0-9_-]{2,31}$ ]]
}

crear_usuario() {
    read -rp "Nombre de usuario a crear: " nombre

    if ! validar_nombre_usuario "$nombre"; then
        echo "Nombre invalido. Use minusculas, numeros, '_' o '-' (3 a 32 caracteres, sin empezar con numero)."
        return
    fi

    if id "$nombre" &>/dev/null; then
        echo "El usuario '$nombre' ya existe."
        return
    fi

    read -rp "Nombre completo (comentario, opcional): " comentario
    read -rp "Agregar al grupo wheel (administracion)? (s/n): " es_wheel

    if useradd -m -s /bin/bash -c "$comentario" "$nombre"; then
        echo "Usuario '$nombre' creado correctamente."
    else
        echo "Error: no se pudo crear el usuario '$nombre'."
        return
    fi

    echo "Defini la contrasena para '$nombre':"
    if ! passwd "$nombre"; then
        echo "Advertencia: no se pudo establecer la contrasena ahora. Podes hacerlo luego con: passwd $nombre"
    fi

    if [[ "$es_wheel" == "s" ]]; then
        if usermod -aG wheel "$nombre"; then
            echo "Usuario '$nombre' agregado al grupo wheel."
        else
            echo "Error: no se pudo agregar '$nombre' al grupo wheel."
        fi
    fi

    registrar_log "usuario creado: $nombre (wheel: $es_wheel)"
}

eliminar_usuario() {
    read -rp "Nombre de usuario a eliminar: " nombre

    if ! id "$nombre" &>/dev/null; then
        echo "El usuario '$nombre' no existe."
        return
    fi

    if [[ "$nombre" == "root" ]]; then
        echo "Operacion no permitida: no se puede eliminar el usuario root."
        return
    fi

    local uid_objetivo
    uid_objetivo=$(id -u "$nombre")
    if (( uid_objetivo < UID_MINIMO )); then
        echo "Operacion no permitida: '$nombre' es un usuario del sistema (UID $uid_objetivo < $UID_MINIMO)."
        return
    fi

    read -rp "Eliminar tambien su carpeta home? (s/n): " borrar_home
    read -rp "Confirmas eliminar a '$nombre'? (s/n): " confirmar

    if [[ "$confirmar" != "s" ]]; then
        echo "Operacion cancelada."
        return
    fi

    local resultado_borrado
    if [[ "$borrar_home" == "s" ]]; then
        userdel -r "$nombre"
        resultado_borrado=$?
    else
        userdel "$nombre"
        resultado_borrado=$?
    fi

    if [[ $resultado_borrado -eq 0 ]]; then
        registrar_log "usuario eliminado: $nombre (home borrado: $borrar_home)"
        echo "Usuario '$nombre' eliminado."
    else
        echo "Error: no se pudo eliminar '$nombre'."
    fi
}

modificar_usuario() {
    read -rp "Nombre de usuario a modificar: " nombre

    if ! id "$nombre" &>/dev/null; then
        echo "El usuario '$nombre' no existe."
        return
    fi

    echo "1) Agregar a un grupo"
    echo "2) Cambiar shell"
    echo "3) Bloquear cuenta"
    echo "4) Desbloquear cuenta"
    echo "5) Cambiar nombre completo (comentario)"
    read -rp "Opcion: " opcion

    case $opcion in
        1)
            read -rp "Nombre del grupo: " grupo
            if ! getent group "$grupo" > /dev/null; then
                echo "El grupo '$grupo' no existe."
                return
            fi
            if usermod -aG "$grupo" "$nombre"; then
                registrar_log "usuario $nombre agregado al grupo $grupo"
                echo "Usuario '$nombre' agregado al grupo '$grupo'."
            else
                echo "Error al agregar al grupo."
            fi
            ;;
        2)
            read -rp "Nueva shell (ej: /bin/bash): " shell
            if ! grep -qx "$shell" /etc/shells; then
                echo "La shell '$shell' no es valida (no figura en /etc/shells)."
                return
            fi
            if usermod -s "$shell" "$nombre"; then
                registrar_log "shell de $nombre cambiada a $shell"
                echo "Shell actualizada correctamente."
            else
                echo "Error al cambiar la shell."
            fi
            ;;
        3)
            if [[ "$nombre" == "root" ]]; then
                echo "Operacion no permitida: no se puede bloquear al usuario root."
                return
            fi
            if usermod -L "$nombre"; then
                registrar_log "cuenta $nombre bloqueada"
                echo "Cuenta bloqueada."
            else
                echo "Error al bloquear la cuenta."
            fi
            ;;
        4)
            if usermod -U "$nombre"; then
                registrar_log "cuenta $nombre desbloqueada"
                echo "Cuenta desbloqueada."
            else
                echo "Error al desbloquear la cuenta."
            fi
            ;;
        5)
            read -rp "Nuevo nombre completo: " comentario
            if usermod -c "$comentario" "$nombre"; then
                registrar_log "comentario de $nombre actualizado"
                echo "Nombre completo actualizado."
            else
                echo "Error al actualizar el comentario."
            fi
            ;;
        *)
            echo "Opcion invalida."
            ;;
    esac
}

listar_usuarios() {
    echo ""
    printf "%-15s %-8s %-30s\n" "USUARIO" "UID" "HOME"
    echo "--------------------------------------------------------"
    awk -F: -v min="$UID_MINIMO" '$3 >= min && $3 < 65534 {printf "%-15s %-8s %-30s\n", $1, $3, $6}' /etc/passwd
    echo ""
}

info_usuario() {
    read -rp "Usuario a consultar: " nombre

    if ! id "$nombre" &>/dev/null; then
        echo "El usuario '$nombre' no existe."
        return
    fi

    echo ""
    echo "Identidad:   $(id "$nombre")"
    echo "Comentario:  $(getent passwd "$nombre" | cut -d: -f5)"
    echo "Shell:       $(getent passwd "$nombre" | cut -d: -f7)"
    echo "Home:        $(getent passwd "$nombre" | cut -d: -f6)"
    echo "Grupos:      $(groups "$nombre" | cut -d: -f2)"
    echo ""
}

cambiar_password() {
    read -rp "Usuario al que cambiarle la contrasena: " nombre

    if ! id "$nombre" &>/dev/null; then
        echo "El usuario '$nombre' no existe."
        return
    fi

    if passwd "$nombre"; then
        registrar_log "contrasena modificada para: $nombre"
    else
        echo "Error al cambiar la contrasena."
    fi
}

# Menu principal
while true; do
    echo ""
    echo "===== Zenko - Gestion de Usuarios del Sistema ====="
    echo "1) Crear usuario"
    echo "2) Eliminar usuario"
    echo "3) Modificar usuario"
    echo "4) Listar usuarios"
    echo "5) Cambiar contrasena"
    echo "6) Ver informacion detallada de un usuario"
    echo "7) Salir"
    read -rp "Selecciona una opcion: " opcion

    case $opcion in
        1) crear_usuario ;;
        2) eliminar_usuario ;;
        3) modificar_usuario ;;
        4) listar_usuarios ;;
        5) cambiar_password ;;
        6) info_usuario ;;
        7) echo "Saliendo..."; exit 0 ;;
        *) echo "Opcion invalida." ;;
    esac
done
