namespace Microsoft.Win32.SafeHandles
{
    using Microsoft.Win32;
    using System;
    using System.Runtime.InteropServices;
    using System.Security;

    [SecurityCritical]
    public sealed class SafeAccessTokenHandle : SafeHandle
    {
        private SafeAccessTokenHandle() : base(IntPtr.Zero, true)
        {
        }

        public SafeAccessTokenHandle(IntPtr handle) : base(IntPtr.Zero, true)
        {
            base.SetHandle(handle);
        }

        [SecurityCritical]
        protected override bool ReleaseHandle() => 
            Win32Native.CloseHandle(base.handle);

        public static SafeAccessTokenHandle InvalidHandle =>
            new SafeAccessTokenHandle(IntPtr.Zero);

        public override bool IsInvalid =>
            (base.handle == IntPtr.Zero) || (base.handle == new IntPtr(-1));
    }
}

