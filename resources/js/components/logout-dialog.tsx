import { useMobileNavigation } from "@/hooks/use-mobile-navigation";
import { logout } from "@/routes";
import { router } from "@inertiajs/react";
import { ReactNode, useState } from "react";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "./ui/dialog";
import { Button } from "./ui/button";
import { Spinner } from "./ui/spinner";
import { LogOut } from "lucide-react";

type Props = {
    children: ReactNode;
};

export function LogoutDialog({ children }: Props) {
    const cleanup = useMobileNavigation();
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    const handleLogout = () => {
        cleanup();
        router.flushAll();

        router.visit(logout(), {
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setOpen(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent onCloseAutoFocus={cleanup}>
                <DialogHeader>
                    <DialogTitle>Are you sure you want to log out?</DialogTitle>
                    <DialogDescription>
                        This action will log you out of your account.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={() => setOpen(false)}
                        disabled={processing}
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        onClick={handleLogout}
                        disabled={processing}
                        data-test="confirm-logout-button"
                    >
                        {processing ? <Spinner /> : <LogOut />}
                        Log out
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
