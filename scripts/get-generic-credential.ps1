param(
    [string]$TargetName
)

$credManagerCode = @"
using System;
using System.Runtime.InteropServices;
using System.Text;

public class CredManager
{
    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Unicode)]
    public struct CREDENTIAL
    {
        public uint Flags;
        public uint Type;
        [MarshalAs(UnmanagedType.LPWStr)]
        public string TargetName;
        [MarshalAs(UnmanagedType.LPWStr)]
        public string Comment;
        public System.Runtime.InteropServices.ComTypes.FILETIME LastWritten;
        public uint CredentialBlobSize;
        public IntPtr CredentialBlob;
        public uint Persist;
        public uint AttributeCount;
        public IntPtr Attributes;
        [MarshalAs(UnmanagedType.LPWStr)]
        public string TargetAlias;
        [MarshalAs(UnmanagedType.LPWStr)]
        public string UserName;
    }

    [DllImport("advapi32.dll", EntryPoint = "CredReadW", CharSet = CharSet.Unicode, SetLastError = true)]
    public static extern bool CredRead(string target, uint type, int reservedFlag, out IntPtr credentialPtr);

    [DllImport("advapi32.dll", SetLastError = true)]
    public static extern bool CredFree(IntPtr buffer);

    public static string GetPassword(string targetName)
    {
        IntPtr credPtr;
        if (CredRead(targetName, 1, 0, out credPtr))
        {
            CREDENTIAL cred = (CREDENTIAL)Marshal.PtrToStructure(credPtr, typeof(CREDENTIAL));
            byte[] passwordBytes = new byte[cred.CredentialBlobSize];
            Marshal.Copy(cred.CredentialBlob, passwordBytes, 0, (int)cred.CredentialBlobSize);
            string password = Encoding.Unicode.GetString(passwordBytes);
            CredFree(credPtr);
            return password;
        }
        return null;
    }
}
"@

Add-Type -TypeDefinition $credManagerCode

$password = [CredManager]::GetPassword($TargetName)
if ($password) {
    Write-Output $password
} else {
    Write-Error "Credential not found: $TargetName"
    exit 1
}
