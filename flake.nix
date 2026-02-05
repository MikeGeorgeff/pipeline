{
  inputs = {
    nixpkgs.url = "github:nixos/nixpkgs/nixos-unstable";
  };

  outputs = { self, nixpkgs }:
  let
    pkgs = nixpkgs.legacyPackages.x86_64-linux.pkgs;
    ext = ({ enabled, all }: enabled ++ [ all.redis ]);

    # Helper to create shells for a given PHP version
    mkPhpShells = name: php: {
      "${name}" = pkgs.mkShell {
        name = "${name} Environment";
        buildInputs = [ php php.packages.composer ];
      };

      "${name}-update" = pkgs.mkShell {
        buildInputs = [ php php.packages.composer ];
        shellHook = ''
          composer update
          exit
        '';
      };

      "${name}-test" = pkgs.mkShell {
        buildInputs = [ php php.packages.composer ];
        shellHook = ''
          composer test
          exit
        '';
      };

      "${name}-analyze" = pkgs.mkShell {
        buildInputs = [ php php.packages.composer ];
        shellHook = ''
          composer analyze
          exit
        '';
      };

    };
  in
  {
    devShells.x86_64-linux =
      (mkPhpShells "php82" (pkgs.php82.withExtensions ext)) //
      (mkPhpShells "php84" (pkgs.php.withExtensions ext));
  };
}
