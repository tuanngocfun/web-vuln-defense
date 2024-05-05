namespace Microsoft.Reflection
{
    using System;
    using System.Reflection;
    using System.Runtime.CompilerServices;

    internal static class ReflectionExtensions
    {
        public static System.Reflection.Assembly Assembly(this Type type) => 
            type.Assembly;

        public static Type BaseType(this Type type) => 
            type.BaseType;

        public static TypeCode GetTypeCode(this Type type) => 
            Type.GetTypeCode(type);

        public static bool IsAbstract(this Type type) => 
            type.IsAbstract;

        public static bool IsEnum(this Type type) => 
            type.IsEnum;

        public static bool IsSealed(this Type type) => 
            type.IsSealed;

        public static bool ReflectionOnly(this System.Reflection.Assembly assm) => 
            assm.ReflectionOnly;
    }
}

