#!/bin/sh
set -e
Shell=sh
Script="$(realpath "$0")"

Ps2VmcPath=/mnt/media_rw/8A3A-912B/VMC/games.bin
EmuVmcPath=/sdcard/Android/data/xyz.aethersx2.android/files/memcards/games.ps2
TrySuperuser=true
# NOTE: Script assumes any (formatted) copy of both the PS2 and EMU VMC files is present on server at location ${RemoteVmcDir} prior to execution
RemoteVmcDir=/home/tux/.local/share/Ps2EmuVmcConvert
RemoteScriptPath=/Main/Server/Scripts/Reserved/Ps2EmuVmcConvert.sh
RemoteSshAddr=192.168.1.125
RemoteSshUser=tux
RemoteSshPass="-f /data/data/com.termux/files/home/.sshpass/tux@serbian"

# Exit when no valid option
[ "$1" != FromPs2ToEmu ] && [ "$1" != FromEmuToPs2 ] && { echo "Usage: $0 <FromPs2ToEmu|FromEmuToPs2>"; exit; }

# Ensure superuser if necessary
[ "${TrySuperuser}" = true ] && [ "$(whoami)" != root ] && { sudo "${Shell}" "${Script}" $@; exit; }
SuperDo(){ [ "${TrySuperuser}" = true ] && [ "$(whoami)" != root ] && sudo $@ || $@ ;}

# Optional overriding of VMC paths via CLI
if [ -n "$2" ] && [ -n "$3" ]
then
	[ "$1" = FromPs2ToEmu ] && { Ps2VmcPath="$2"; EmuVmcPath="$3"; }
	[ "$1" = FromEmuToPs2 ] && { EmuVmcPath="$2"; Ps2VmcPath="$3"; }
fi

# Setup some variables
SshExec(){ sshpass ${RemoteSshPass} ssh "${RemoteSshUser}@${RemoteSshAddr}" -t "$1" ;}
ScpExec(){ sshpass ${RemoteSshPass} scp -C $@ ;}
Ps2VmcPath="$(SuperDo realpath "${Ps2VmcPath}")"
EmuVmcPath="$(SuperDo realpath "${EmuVmcPath}")"
[ "$1" = FromPs2ToEmu ] && { VmcFromPath="${Ps2VmcPath}"; VmcToPath="${EmuVmcPath}"; }
[ "$1" = FromEmuToPs2 ] && { VmcFromPath="${EmuVmcPath}"; VmcToPath="${Ps2VmcPath}"; }
RemoteVmcFromPath="${RemoteVmcDir}/$(basename "${VmcFromPath}")"
RemoteVmcToPath="${RemoteVmcDir}/$(basename "${VmcToPath}")"

# Copy VMC from here to server
SuperDo ScpExec "${VmcFromPath}" "${RemoteSshUser}@${RemoteSshAddr}:${RemoteVmcFromPath}"

# Run conversion on server
SshExec "sh '${RemoteScriptPath}' '$1' '${RemoteVmcFromPath}' '${RemoteVmcToPath}'"

# Download converted VMC
SuperDo ScpExec "${RemoteSshUser}@${RemoteSshAddr}:${RemoteVmcToPath}" "${VmcToPath}"
SuperDo chmod +rw "${VmcToPath}"
